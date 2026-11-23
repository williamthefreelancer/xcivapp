/**
 * Videos & Audio hub tabs + WebRTC live audio rooms for hybrid events.
 */
(function () {
	'use strict';

	var cfg = window.snfMedia || {};
	var restBase = (cfg.restUrl || '').replace(/\/$/, '');
	var nonce = cfg.nonce || '';
	var iceServers = Array.isArray(cfg.ice) ? cfg.ice : [{ urls: 'stun:stun.l.google.com:19302' }];
	var i18n = cfg.i18n || {};

	function headers() {
		return {
			'Content-Type': 'application/json',
			'X-WP-Nonce': nonce,
		};
	}

	function initTabs() {
		var hub = document.querySelector('[data-snf-media-hub]');
		if (!hub) return;
		var tabs = hub.querySelectorAll('.snf-media-hub__tab');
		tabs.forEach(function (tab) {
			tab.addEventListener('click', function () {
				tabs.forEach(function (t) {
					t.classList.remove('is-active');
				});
				tab.classList.add('is-active');
			});
		});
	}

	function LiveRoom(root) {
		this.root = root;
		this.room = root.getAttribute('data-snf-live-room') || '';
		this.pc = null;
		this.localStream = null;
		this.pollTimer = null;
		this.isHost = false;
		this.statusEl = root.querySelector('[data-snf-live-status]');
		this.remoteAudio = root.querySelector('[data-snf-live-remote]');
		this.joinBtn = root.querySelector('[data-snf-live-join]');
		this.hostBtn = root.querySelector('[data-snf-live-host]');
		this.leaveBtn = root.querySelector('[data-snf-live-leave]');

		var self = this;
		if (this.joinBtn) {
			this.joinBtn.addEventListener('click', function () {
				self.join(false);
			});
		}
		if (this.hostBtn) {
			this.hostBtn.addEventListener('click', function () {
				self.join(true);
			});
		}
		if (this.leaveBtn) {
			this.leaveBtn.addEventListener('click', function () {
				self.leave();
			});
		}
	}

	LiveRoom.prototype.setStatus = function (msg) {
		if (this.statusEl) {
			this.statusEl.textContent = msg || '';
		}
	};

	LiveRoom.prototype.api = function (method, path, body) {
		var opts = {
			method: method,
			headers: headers(),
			credentials: 'same-origin',
		};
		if (body) {
			opts.body = JSON.stringify(body);
		}
		return fetch(restBase + path, opts).then(function (res) {
			if (!res.ok) {
				return res.json().then(function (err) {
					throw new Error((err && err.message) || 'Request failed');
				});
			}
			return res.json();
		});
	};

	LiveRoom.prototype.ensurePeer = function () {
		if (this.pc) return this.pc;
		var self = this;
		this.pc = new RTCPeerConnection({ iceServers: iceServers });
		this.pc.ontrack = function (ev) {
			if (self.remoteAudio && ev.streams && ev.streams[0]) {
				self.remoteAudio.srcObject = ev.streams[0];
			}
		};
		this.pc.onicecandidate = function (ev) {
			if (!ev.candidate || !self._peerId) return;
			self.api('POST', '/room/' + encodeURIComponent(self.room) + '/signal', {
				type: 'ice',
				to: self._peerId,
				payload: ev.candidate,
			}).catch(function () {});
		};
		return this.pc;
	};

	LiveRoom.prototype.join = function (asHost) {
		var self = this;
		if (!restBase || !this.room) {
			this.setStatus('Live audio is not configured.');
			return;
		}
		if (!nonce) {
			window.location.href = cfg.loginUrl || '/member-login/';
			return;
		}

		this.isHost = !!asHost;
		var action = asHost ? 'claim_host' : 'join';

		this.api('POST', '/room/' + encodeURIComponent(this.room) + '/signal', { action: action })
			.then(function () {
				if (self.leaveBtn) self.leaveBtn.hidden = false;
				self.setStatus(asHost ? i18n.hosting || 'Hosting…' : i18n.listening || 'Listening…');
				return self.startMedia();
			})
			.then(function () {
				self.startPolling();
			})
			.catch(function (err) {
				self.setStatus(err.message || 'Could not join room');
			});
	};

	LiveRoom.prototype.startMedia = function () {
		var self = this;
		if (!this.isHost) {
			this.ensurePeer();
			return Promise.resolve();
		}
		return navigator.mediaDevices.getUserMedia({ audio: true, video: false }).then(function (stream) {
			self.localStream = stream;
			var pc = self.ensurePeer();
			stream.getTracks().forEach(function (track) {
				pc.addTrack(track, stream);
			});
		});
	};

	LiveRoom.prototype.startPolling = function () {
		var self = this;
		if (this.pollTimer) clearInterval(this.pollTimer);
		var tick = function () {
			self.api('GET', '/room/' + encodeURIComponent(self.room) + '/signal')
				.then(function (data) {
					self.handleSignals(data);
				})
				.catch(function () {});
		};
		tick();
		this.pollTimer = setInterval(tick, 2500);
	};

	LiveRoom.prototype.handleSignals = function (data) {
		var self = this;
		var signals = (data && data.signals) || [];
		var host = data && data.host ? parseInt(data.host, 10) : 0;
		var peers = (data && data.peers) || [];

		if (this.isHost && peers.length) {
			peers.forEach(function (peerId) {
				peerId = parseInt(peerId, 10);
				if (!peerId || peerId === host) return;
				if (self._offered && self._offered[peerId]) return;
				self._offered = self._offered || {};
				self._offered[peerId] = true;
				self._peerId = peerId;
				self.createOffer(peerId);
			});
		}

		signals.forEach(function (signal) {
			self.applySignal(signal);
		});
	};

	LiveRoom.prototype.createOffer = function (peerId) {
		var self = this;
		var pc = this.ensurePeer();
		pc.createOffer()
			.then(function (offer) {
				return pc.setLocalDescription(offer).then(function () {
					return self.api('POST', '/room/' + encodeURIComponent(self.room) + '/signal', {
						type: 'offer',
						to: peerId,
						payload: offer,
					});
				});
			})
			.catch(function () {});
	};

	LiveRoom.prototype.applySignal = function (signal) {
		var self = this;
		var pc = this.ensurePeer();
		var type = signal.type;
		var from = parseInt(signal.from, 10) || 0;
		var payload = signal.payload;
		this._peerId = from;

		if (type === 'offer' && !this.isHost) {
			pc.setRemoteDescription(new RTCSessionDescription(payload))
				.then(function () {
					return pc.createAnswer();
				})
				.then(function (answer) {
					return pc.setLocalDescription(answer).then(function () {
						return self.api('POST', '/room/' + encodeURIComponent(self.room) + '/signal', {
							type: 'answer',
							to: from,
							payload: answer,
						});
					});
				})
				.catch(function () {});
		} else if (type === 'answer' && this.isHost) {
			pc.setRemoteDescription(new RTCSessionDescription(payload)).catch(function () {});
		} else if (type === 'ice' && payload) {
			pc.addIceCandidate(new RTCIceCandidate(payload)).catch(function () {});
		}
	};

	LiveRoom.prototype.leave = function () {
		if (this.pollTimer) {
			clearInterval(this.pollTimer);
			this.pollTimer = null;
		}
		if (this.localStream) {
			this.localStream.getTracks().forEach(function (t) {
				t.stop();
			});
			this.localStream = null;
		}
		if (this.pc) {
			this.pc.close();
			this.pc = null;
		}
		if (this.remoteAudio) {
			this.remoteAudio.srcObject = null;
		}
		if (this.leaveBtn) this.leaveBtn.hidden = true;
		this._offered = {};
		this.api('POST', '/room/' + encodeURIComponent(this.room) + '/signal', { action: 'leave' }).catch(function () {});
		this.setStatus('');
	};

	function initLiveRooms() {
		if (!cfg.webrtc) return;
		document.querySelectorAll('[data-snf-live-room]').forEach(function (root) {
			if (root.getAttribute('data-snf-live-provider') !== 'webrtc') return;
			new LiveRoom(root);
		});
	}

	document.addEventListener('DOMContentLoaded', function () {
		initTabs();
		initLiveRooms();
	});
})();
