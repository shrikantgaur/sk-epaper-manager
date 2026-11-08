/**
 * SK ePaper Manager - review notice.
 *
 * "Yes" and "Not really" are real links that open wordpress.org in a new tab;
 * the answer is recorded in the background. "Maybe later" and "Already did"
 * are recorded the same way and the notice is removed. Every control also has
 * a nonced fallback URL, used when JavaScript is off or the request fails.
 */
(function () {
  'use strict';

  function ready(fn) {
    if (document.readyState !== 'loading') {
      fn();
    } else {
      document.addEventListener('DOMContentLoaded', fn);
    }
  }

  function post(config, choice) {
    var body = new URLSearchParams();
    body.append('action', config.action);
    body.append('nonce', config.nonce);
    body.append('choice', choice);

    return fetch(config.ajax_url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: body.toString()
    }).then(function (response) {
      return response.json();
    }).then(function (json) {
      if (!json || !json.success) throw new Error('rejected');
      return json;
    });
  }

  function speak(text) {
    if (window.wp && window.wp.a11y && typeof window.wp.a11y.speak === 'function' && text) {
      window.wp.a11y.speak(text.replace(/\s+/g, ' ').trim());
    }
  }

  /*
   * "New in 2.1" share notice: Enable switches the share bar on, Dismiss (or
   * the core x) hides the notice for good. Same fallback-link approach.
   */
  ready(function () {
    var notice = document.getElementById('sk-epaper-share-notice');
    if (!notice || typeof sk_epaper_share_notice === 'undefined') return;

    var config = sk_epaper_share_notice;
    var answered = false;

    function handle(control, event) {
      var choice = control.getAttribute('data-sk-share');
      if (!choice || answered) return;
      if (event) event.preventDefault();
      answered = true;
      notice.classList.add('sk-review-is-busy');

      post(config, choice).then(function () {
        notice.classList.remove('sk-review-is-busy');

        if (choice === 'enable') {
          var body = notice.querySelector('.sk-share-notice-body');
          var done = notice.querySelector('.sk-share-notice-done');
          if (body && done) {
            body.hidden = true;
            done.hidden = false;
            done.setAttribute('tabindex', '-1');
            done.focus();
            speak(done.textContent);
            return;
          }
        }

        notice.parentNode && notice.parentNode.removeChild(notice);
      }).catch(function () {
        var fallback = control.getAttribute('href');
        if (fallback) window.location.href = fallback;
      });
    }

    notice.addEventListener('click', function (event) {
      var control = event.target.closest('[data-sk-share]');
      if (control && notice.contains(control)) {
        handle(control, event);
        return;
      }

      if (event.target.closest('.notice-dismiss') && !answered) {
        answered = true;
        post(config, 'dismiss').catch(function () {});
      }
    });

    notice.addEventListener('keydown', function (event) {
      if (event.key !== ' ' && event.key !== 'Spacebar') return;
      var control = event.target.closest('[role="button"][data-sk-share]');
      if (!control) return;
      event.preventDefault();
      handle(control, event);
    });
  });

  ready(function () {
    var notice = document.getElementById('sk-epaper-review-notice');

    if (!notice || typeof sk_epaper_review === 'undefined') return;

    var config = sk_epaper_review;
    var answered = false;

    // Without JavaScript the wordpress.org links go through the nonced
    // fallback, which records the answer and redirects. With it, point them
    // straight at wordpress.org and record the answer over AJAX.
    Array.prototype.forEach.call(notice.querySelectorAll('[data-direct]'), function (link) {
      link.setAttribute('href', link.getAttribute('data-direct'));
    });

    function send(choice) {
      var body = new URLSearchParams();
      body.append('action', config.action);
      body.append('nonce', config.nonce);
      body.append('choice', choice);

      return fetch(config.ajax_url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: body.toString()
      }).then(function (response) {
        return response.json();
      }).then(function (json) {
        if (!json || !json.success) throw new Error('rejected');
        return json;
      });
    }

    function announce(text) {
      if (window.wp && window.wp.a11y && typeof window.wp.a11y.speak === 'function' && text) {
        window.wp.a11y.speak(text);
      }
    }

    function removeNotice() {
      notice.parentNode && notice.parentNode.removeChild(notice);
    }

    function showThanks(choice) {
      var body = notice.querySelector('.sk-review-notice-body');
      var thanks = notice.querySelector('.sk-review-notice-thanks');
      if (!body || !thanks) {
        removeNotice();
        return;
      }

      Array.prototype.forEach.call(thanks.querySelectorAll('[data-sk-thanks]'), function (line) {
        line.hidden = line.getAttribute('data-sk-thanks') !== choice;
      });

      body.hidden = true;
      thanks.hidden = false;

      var line = thanks.querySelector('[data-sk-thanks="' + choice + '"]');
      if (line) {
        line.setAttribute('tabindex', '-1');
        line.focus();
        announce(line.textContent.replace(/\s+/g, ' ').trim());
      }
    }

    function handle(control, event) {
      var choice = control.getAttribute('data-sk-review');
      if (!choice || answered) return;

      var opensTab = choice === 'rate' || choice === 'feedback';

      // For the two wordpress.org links the browser opens the tab itself;
      // only the in-place answers need the default navigation stopped.
      if (!opensTab && event) event.preventDefault();

      answered = true;

      if (opensTab) {
        showThanks(choice);
        send(choice).catch(function () {
          // The tab already opened; the notice simply returns next time.
          answered = false;
        });
        return;
      }

      notice.classList.add('sk-review-is-busy');

      send(choice).then(function () {
        removeNotice();
        announce(control.textContent.replace(/\s+/g, ' ').trim());
      }).catch(function () {
        // Fall back to the nonced link so the answer is not lost.
        var fallback = control.getAttribute('href');
        if (fallback) window.location.href = fallback;
      });
    }

    notice.addEventListener('click', function (event) {
      var control = event.target.closest('[data-sk-review]');
      if (control && notice.contains(control)) {
        handle(control, event);
        return;
      }

      // Core's dismiss button (added by common.js) counts as "Maybe later".
      if (event.target.closest('.notice-dismiss') && !answered) {
        answered = true;
        send('later').catch(function () {});
      }
    });

    // The in-place answers are links styled as buttons: give them the Space
    // key a button would have.
    notice.addEventListener('keydown', function (event) {
      if (event.key !== ' ' && event.key !== 'Spacebar') return;
      var control = event.target.closest('[role="button"][data-sk-review]');
      if (!control) return;
      event.preventDefault();
      handle(control, event);
    });
  });
})();
