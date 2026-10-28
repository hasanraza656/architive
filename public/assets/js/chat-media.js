/* ==========================================================================
   Chat recorders, loaded only when someone presses the microphone or screen button.
     PortalChat.media.voice({ host, max, onDone(file, {duration, peaks}) })
     PortalChat.media.screen({ max, maxBytes, onDone(file, {duration, preview, previewUrl, width, height}) })
   Nothing is uploaded here: the finished recording is handed back to the composer, where the person can still
   add a message or discard it before sending.
   ========================================================================== */
(function () {
  'use strict';

  window.PortalChat = window.PortalChat || {};
  var host = window.PortalChat.api;               // toast, ico, mmss ...
  function toast(t) { (host && host.toast ? host.toast : window.alert)(t); }
  function ico(n) { return host.ico(n); }
  function mmss(s) { return host.mmss(s); }
  function esc(s) { return host.esc(s); }

  function pickMime(list) { for (var i = 0; i < list.length; i++) { if (window.MediaRecorder && MediaRecorder.isTypeSupported(list[i])) { return list[i]; } } return ''; }
  function extFor(mime, fallback) { return /mp4/.test(mime) ? (fallback === 'webm' ? 'mp4' : 'm4a') : (/ogg/.test(mime) ? 'ogg' : fallback); }
  function micError(e) {
    if (!e) { return 'Recording is not available here.'; }
    if (e.name === 'NotAllowedError' || e.name === 'SecurityError') { return 'The microphone is blocked. Allow it with the lock icon in the address bar, then try again.'; }
    if (e.name === 'NotFoundError' || e.name === 'DevicesNotFoundError') { return 'No microphone was found on this device.'; }
    if (e.name === 'NotReadableError') { return 'The microphone is being used by another app.'; }
    return 'The microphone could not be started.';
  }

  /* ------------------------------------------------------------------ voice message */
  function voice(opts) {
    var row = opts.host, form = row.parentNode, max = opts.max || 600;
    if (form.querySelector('.vrec')) { return; }
    navigator.mediaDevices.getUserMedia({ audio: { echoCancellation: true, noiseSuppression: true, autoGainControl: true } }).then(function (stream) {
      var mime = pickMime(['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4', 'audio/ogg;codecs=opus', 'audio/ogg']);
      var rec; try { rec = new MediaRecorder(stream, mime ? { mimeType: mime, audioBitsPerSecond: 48000 } : undefined); } catch (e) { stream.getTracks().forEach(function (t) { t.stop(); }); toast('Voice recording is not supported in this browser.'); return; }
      var chunks = [], samples = [], t0 = Date.now(), pausedAt = 0, pausedTotal = 0, timer = null, ctx = null, an = null, buf = null, cancelled = false, finished = false;

      var ui = document.createElement('div'); ui.className = 'vrec';
      ui.innerHTML = '<button class="vrec__x" type="button" aria-label="Discard recording">' + ico('x') + '</button><span class="vrec__dot" aria-hidden="true"></span><span class="vrec__t" data-t>0:00</span><div class="vrec__wave" data-w aria-hidden="true"></div>' +
        '<button class="vrec__pause" type="button" data-pause aria-label="Pause recording">' + ico('pause') + '</button><button class="vrec__send" type="button" data-done aria-label="Finish recording">' + ico('check') + '</button>';
      row.hidden = true; form.insertBefore(ui, row);
      var wave = ui.querySelector('[data-w]'), tEl = ui.querySelector('[data-t]'), bars = [];

      function elapsed() { return ((pausedAt || Date.now()) - t0 - pausedTotal) / 1000; }
      try {
        var AC = window.AudioContext || window.webkitAudioContext; ctx = new AC(); an = ctx.createAnalyser(); an.fftSize = 512; ctx.createMediaStreamSource(stream).connect(an); buf = new Uint8Array(an.fftSize);
      } catch (e) {}
      function level() { if (!an) { return 30; } an.getByteTimeDomainData(buf); var s = 0; for (var i = 0; i < buf.length; i++) { var v = (buf[i] - 128) / 128; s += v * v; } return Math.min(100, Math.round(Math.sqrt(s / buf.length) * 320)); }
      timer = setInterval(function () {
        if (rec.state !== 'recording') { return; }
        var sec = elapsed(); tEl.textContent = mmss(sec);
        var l = level(); samples.push(l);
        var i = document.createElement('i'); i.style.height = Math.max(8, l) + '%'; wave.appendChild(i); bars.push(i);
        while (wave.children.length > 60) { wave.removeChild(wave.firstChild); }
        if (sec >= max) { finish(); }
      }, 100);

      function cleanup() {
        clearInterval(timer); stream.getTracks().forEach(function (t) { t.stop(); }); try { ctx && ctx.close(); } catch (e) {}
        ui.remove(); row.hidden = false;
      }
      function finish() { if (finished) { return; } finished = true; try { rec.stop(); } catch (e) { cleanup(); } }
      rec.ondataavailable = function (e) { if (e.data && e.data.size) { chunks.push(e.data); } };
      rec.onstop = function () {
        var duration = Math.max(1, Math.round(elapsed())), blob = new Blob(chunks, { type: mime || 'audio/webm' });
        cleanup();
        if (cancelled || blob.size < 600) { if (!cancelled) { toast('That recording was too short.'); } return; }
        // 48 bars for the waveform: the loudest sample in each slice
        var n = 48, peaks = []; for (var b = 0; b < n; b++) { var from = Math.floor(b * samples.length / n), to = Math.max(from + 1, Math.floor((b + 1) * samples.length / n)), m = 0; for (var k = from; k < to && k < samples.length; k++) { m = Math.max(m, samples[k]); } peaks.push(m); }
        var top = Math.max.apply(null, peaks.concat([1])); peaks = peaks.map(function (v) { return Math.max(10, Math.round(v / top * 100)); });
        var file = new File([blob], 'voice-' + Date.now() + '.' + extFor(blob.type, 'webm'), { type: blob.type });
        opts.onDone(file, { duration: duration, peaks: peaks });
      };
      ui.querySelector('.vrec__x').addEventListener('click', function () { cancelled = true; finish(); });
      ui.querySelector('[data-done]').addEventListener('click', finish);
      ui.querySelector('[data-pause]').addEventListener('click', function (e) {
        var b = e.currentTarget;
        if (rec.state === 'recording') { rec.pause(); pausedAt = Date.now(); ui.classList.add('is-paused'); b.innerHTML = ico('play'); b.setAttribute('aria-label', 'Resume recording'); }
        else if (rec.state === 'paused') { rec.resume(); pausedTotal += Date.now() - pausedAt; pausedAt = 0; ui.classList.remove('is-paused'); b.innerHTML = ico('pause'); b.setAttribute('aria-label', 'Pause recording'); }
      });
      ui.addEventListener('keydown', function (e) { if (e.key === 'Escape') { cancelled = true; finish(); } });
      rec.start(250); ui.querySelector('[data-done]').focus();
    }).catch(function (e) { toast(micError(e)); });
  }

  /* ------------------------------------------------------------------ screen recording */
  function screen(opts) {
    if (document.querySelector('.rec-dialog, .rec-bar')) { return; }
    var max = opts.max || 900, maxBytes = opts.maxBytes || 45 * 1048576;

    var dlg = document.createElement('dialog'); dlg.className = 'pmodal rec-dialog'; dlg.setAttribute('aria-labelledby', 'recTitle');
    dlg.innerHTML = '<div class="pmodal__head"><h3 id="recTitle">Record your screen</h3><p>Show something instead of typing it. You choose what to share: a tab, a window or the whole screen. Nothing is sent until you press Send.</p></div>' +
      '<div class="pmodal__body pform"><label class="bcheck"><input type="checkbox" data-mic checked> Add my voice (microphone)</label>' +
      '<label class="bcheck"><input type="checkbox" data-sys> Also record computer sound <small>(only when your browser offers it)</small></label>' +
      '<p class="rec-note">Recordings up to ' + Math.round(max / 60) + ' minutes and about ' + Math.round(maxBytes / 1048576) + ' MB. Do not show passwords or other private things.</p></div>' +
      '<div class="pmodal__foot"><button class="pbtn pbtn--ghost" type="button" data-cancel>Cancel</button><button class="pbtn pbtn--primary" type="button" data-start>' + ico('video') + ' Start recording</button></div>';
    document.body.appendChild(dlg); dlg.showModal();
    dlg.addEventListener('close', function () { dlg.remove(); });
    dlg.querySelector('[data-cancel]').addEventListener('click', function () { dlg.close(); });
    dlg.querySelector('[data-start]').addEventListener('click', function () {
      var wantMic = dlg.querySelector('[data-mic]').checked, wantSys = dlg.querySelector('[data-sys]').checked;
      dlg.close();
      start(wantMic, wantSys);
    });

    function start(wantMic, wantSys) {
      var display = null, mic = null;
      navigator.mediaDevices.getDisplayMedia({ video: { frameRate: { ideal: 20, max: 30 }, width: { max: 1920 }, height: { max: 1080 } }, audio: wantSys })
        .then(function (d) { display = d; return wantMic ? navigator.mediaDevices.getUserMedia({ audio: { echoCancellation: true, noiseSuppression: true } }).catch(function (e) { toast(micError(e) + ' Recording without your voice.'); return null; }) : null; })
        .then(function (m) { mic = m; countdown(function () { record(display, mic); }, function () { stopTracks(display, mic); }); })
        .catch(function (e) { stopTracks(display, mic); if (e && e.name !== 'NotAllowedError') { toast('Screen recording could not be started.'); } });
    }
    function stopTracks() { Array.prototype.forEach.call(arguments, function (s) { s && s.getTracks().forEach(function (t) { t.stop(); }); }); }

    function countdown(go, abort) {
      var n = 3, el = document.createElement('div'); el.className = 'rec-count'; el.setAttribute('role', 'status'); document.body.appendChild(el);
      (function tick() { if (n === 0) { el.remove(); go(); return; } el.innerHTML = '<span>' + n + '</span><small>Recording starts…</small>'; n--; setTimeout(tick, 900); })();
      document.addEventListener('keydown', function esc(e) { if (e.key === 'Escape') { document.removeEventListener('keydown', esc); el.remove(); n = -1; abort(); } });
    }

    function record(display, mic) {
      var ctx = null, tracks = display.getVideoTracks().slice();
      var audioTracks = display.getAudioTracks().concat(mic ? mic.getAudioTracks() : []);
      if (audioTracks.length > 1) {   // mix computer sound and microphone into one track
        try {
          var AC = window.AudioContext || window.webkitAudioContext; ctx = new AC(); var dest = ctx.createMediaStreamDestination();
          [display, mic].forEach(function (s) { if (s && s.getAudioTracks().length) { ctx.createMediaStreamSource(new MediaStream(s.getAudioTracks())).connect(dest); } });
          tracks = tracks.concat(dest.stream.getAudioTracks());
        } catch (e) { tracks = tracks.concat(audioTracks.slice(0, 1)); }
      } else { tracks = tracks.concat(audioTracks); }
      var stream = new MediaStream(tracks);
      var mime = pickMime(['video/webm;codecs=vp9,opus', 'video/webm;codecs=vp8,opus', 'video/webm', 'video/mp4']);
      var rec; try { rec = new MediaRecorder(stream, { mimeType: mime || undefined, videoBitsPerSecond: 1300000, audioBitsPerSecond: 64000 }); } catch (e) { stopTracks(display, mic); toast('Screen recording is not supported in this browser.'); return; }
      var chunks = [], bytes = 0, t0 = Date.now(), pausedAt = 0, pausedTotal = 0, discarded = false, stopped = false;
      var size = display.getVideoTracks()[0].getSettings ? display.getVideoTracks()[0].getSettings() : {};

      var bar = document.createElement('div'); bar.className = 'rec-bar'; bar.setAttribute('role', 'region'); bar.setAttribute('aria-label', 'Screen recording controls');
      bar.innerHTML = '<span class="rec-bar__dot" aria-hidden="true"></span><b data-t>0:00</b><small data-sz>0 MB</small><button type="button" data-pause class="rec-bar__b">' + ico('pause') + ' Pause</button><button type="button" data-stop class="rec-bar__b rec-bar__b--stop">' + ico('stop') + ' Stop</button>';
      document.body.appendChild(bar);
      var tEl = bar.querySelector('[data-t]'), sEl = bar.querySelector('[data-sz]');
      function elapsed() { return ((pausedAt || Date.now()) - t0 - pausedTotal) / 1000; }
      var timer = setInterval(function () {
        tEl.textContent = mmss(elapsed()); sEl.textContent = (bytes / 1048576).toFixed(1) + ' MB';
        if (elapsed() >= max) { stop('time'); } else if (bytes >= maxBytes) { stop('size'); }
      }, 250);
      rec.ondataavailable = function (e) { if (e.data && e.data.size) { chunks.push(e.data); bytes += e.data.size; } };
      function stop(why) { if (stopped) { return; } stopped = true; clearInterval(timer); if (why === 'size') { toast('The recording reached the size limit and was stopped.'); } if (why === 'time') { toast('The recording reached the time limit and was stopped.'); } try { rec.stop(); } catch (e) { cleanupAll(); } }
      function cleanupAll() { clearInterval(timer); stopTracks(display, mic); try { ctx && ctx.close(); } catch (e) {} bar.remove(); }
      rec.onstop = function () {
        var duration = Math.max(1, Math.round(elapsed())), blob = new Blob(chunks, { type: (mime || 'video/webm').split(';')[0] });
        cleanupAll();
        if (discarded || blob.size < 2000) { return; }
        review(blob, duration, size);
      };
      display.getVideoTracks()[0].addEventListener('ended', function () { stop(); });      // the browser's own "Stop sharing" button
      bar.querySelector('[data-stop]').addEventListener('click', function () { stop(); });
      bar.querySelector('[data-pause]').addEventListener('click', function (e) {
        var b = e.currentTarget;
        if (rec.state === 'recording') { rec.pause(); pausedAt = Date.now(); bar.classList.add('is-paused'); b.innerHTML = ico('play') + ' Resume'; }
        else if (rec.state === 'paused') { rec.resume(); pausedTotal += Date.now() - pausedAt; pausedAt = 0; bar.classList.remove('is-paused'); b.innerHTML = ico('pause') + ' Pause'; }
      });
      rec.start(1000);
    }

    /** Preview before sending: play it, then Send / Record again / Discard. */
    function review(blob, duration, size) {
      var url = URL.createObjectURL(blob), d = document.createElement('dialog'); d.className = 'pmodal pmodal--wide rec-dialog rec-review'; d.setAttribute('aria-labelledby', 'recRevTitle');
      d.innerHTML = '<div class="pmodal__head"><h3 id="recRevTitle">Your recording</h3><p>' + mmss(duration) + ' · ' + (blob.size / 1048576).toFixed(1) + ' MB. Watch it once, then send it.</p></div>' +
        '<div class="pmodal__body"><video controls playsinline preload="auto" src="' + url + '"></video></div>' +
        '<div class="pmodal__foot"><button class="pbtn pbtn--ghost" type="button" data-discard>Discard</button><button class="pbtn pbtn--ghost" type="button" data-again>Record again</button><button class="pbtn pbtn--primary" type="button" data-send>' + ico('check') + ' Add to message</button></div>';
      document.body.appendChild(d); d.showModal();
      var v = d.querySelector('video'), sent = false;
      v.addEventListener('loadedmetadata', function () { if (v.duration === Infinity) { v.currentTime = 1e101; v.addEventListener('timeupdate', function fix() { v.removeEventListener('timeupdate', fix); v.currentTime = 0; }); } });
      function close() { d.close(); }
      d.addEventListener('close', function () { setTimeout(function () { d.remove(); if (!sent) { URL.revokeObjectURL(url); } }, 50); });
      d.querySelector('[data-discard]').addEventListener('click', close);
      d.querySelector('[data-again]').addEventListener('click', function () { close(); setTimeout(function () { screen(opts); }, 100); });
      d.querySelector('[data-send]').addEventListener('click', function () {
        sent = true;
        poster(v, size).then(function (p) {
          var file = new File([blob], 'screen-recording-' + Date.now() + '.' + extFor(blob.type, 'webm'), { type: blob.type });
          opts.onDone(file, { duration: duration, preview: p && p.blob, previewUrl: p && p.url, width: v.videoWidth || size.width, height: v.videoHeight || size.height });
          close();
        });
      });
    }
    /** A small still picture of the recording, shown in the chat before it is played */
    function poster(video, size) {
      return new Promise(function (resolve) {
        var done = false; function ok(p) { if (!done) { done = true; resolve(p); } }
        setTimeout(function () { ok(null); }, 4000);
        function grab() {
          try {
            var w = Math.min(640, video.videoWidth || 640), h = Math.round(w * (video.videoHeight || 360) / (video.videoWidth || 640)), c = document.createElement('canvas'); c.width = w; c.height = h;
            c.getContext('2d').drawImage(video, 0, 0, w, h);
            c.toBlob(function (b) { ok(b ? { blob: b, url: URL.createObjectURL(b) } : null); }, 'image/jpeg', 0.8);
          } catch (e) { ok(null); }
        }
        if (video.readyState >= 2) { video.pause(); try { video.currentTime = Math.min(0.6, (video.duration || 1) / 2); } catch (e) {} video.addEventListener('seeked', grab, { once: true }); setTimeout(grab, 700); } else { video.addEventListener('loadeddata', function () { setTimeout(grab, 150); }, { once: true }); }
      });
    }
  }

  window.PortalChat.media = { voice: voice, screen: screen };
}());
