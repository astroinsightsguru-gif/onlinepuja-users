<!-- 🕉️ OnlinePuja AI: Advanced Vedic Voice & Jyotish Assistant Widget ("पंडित जी Voice AI") -->
<style>
  #onlinepuja-voice-widget-btn {
    position: fixed;
    bottom: 24px;
    right: 20px;
    z-index: 999998;
    background: linear-gradient(135deg, #FF6F00 0%, #D84315 100%);
    color: #fff;
    border: 2px solid #FFE082;
    border-radius: 50px;
    padding: 12px 20px;
    box-shadow: 0 10px 25px rgba(230, 81, 0, 0.45), 0 0 15px rgba(255, 215, 0, 0.35);
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 700;
    font-size: 14px;
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    font-family: inherit;
  }
  #onlinepuja-voice-widget-btn:hover {
    transform: translateY(-4px) scale(1.03);
    box-shadow: 0 14px 30px rgba(230, 81, 0, 0.6), 0 0 25px rgba(255, 215, 0, 0.5);
  }
  #onlinepuja-voice-widget-btn .pulse-ring {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background-color: #76FF03;
    box-shadow: 0 0 0 0 rgba(118, 255, 3, 0.7);
    animation: op-pulse 1.8s infinite;
  }
  @keyframes op-pulse {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(118, 255, 3, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(118, 255, 3, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(118, 255, 3, 0); }
  }

  /* Modal Container */
  #onlinepuja-voice-modal {
    position: fixed;
    bottom: 84px;
    right: 20px;
    width: 410px;
    max-width: calc(100vw - 32px);
    height: 610px;
    max-height: calc(100vh - 100px);
    background: #FFFDF9;
    border: 1.5px solid #FFD54F;
    border-radius: 22px;
    box-shadow: 0 22px 50px rgba(0, 0, 0, 0.3), 0 0 35px rgba(255, 152, 0, 0.2);
    z-index: 999999;
    display: none;
    flex-direction: column;
    overflow: hidden;
    font-family: inherit;
    animation: op-slide-up 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  }
  @keyframes op-slide-up {
    from { opacity: 0; transform: translateY(20px) scale(0.96); }
    to { opacity: 1; transform: translateY(0) scale(1); }
  }

  /* Header */
  .op-voice-header {
    background: linear-gradient(135deg, #FF6F00 0%, #BF360C 100%);
    color: white;
    padding: 12px 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 2px solid #FFD54F;
  }
  .op-voice-header h3 {
    margin: 0;
    font-size: 15px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 8px;
    color: #fff;
  }
  .op-voice-header .header-controls {
    display: flex;
    align-items: center;
    gap: 6px;
  }
  .op-voice-header .header-btn {
    background: rgba(255,255,255,0.22);
    border: none;
    color: white;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s;
  }
  .op-voice-header .header-btn:hover {
    background: rgba(255,255,255,0.4);
  }

  /* Kundali Quick Banner */
  .op-kundali-strip {
    background: #FFF8E1;
    border-bottom: 1px solid #FFE082;
    padding: 6px 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 12px;
    color: #BF360C;
    font-weight: 600;
  }
  .op-kundali-strip button {
    background: #FF6F00;
    color: white;
    border: none;
    border-radius: 12px;
    padding: 3px 10px;
    font-size: 11px;
    cursor: pointer;
    font-weight: 700;
  }

  /* Kundali Drawer */
  #op-kundali-drawer {
    display: none;
    background: #FFF3E0;
    border-bottom: 1.5px solid #FFB74D;
    padding: 12px 14px;
    font-size: 12.5px;
  }
  #op-kundali-drawer input {
    width: 100%;
    padding: 6px 10px;
    margin-bottom: 6px;
    border: 1px solid #FFCC80;
    border-radius: 6px;
    font-size: 12px;
    box-sizing: border-box;
  }
  #op-kundali-drawer .save-btn {
    width: 100%;
    background: #FF6F00;
    color: white;
    border: none;
    padding: 7px;
    border-radius: 6px;
    font-weight: 700;
    cursor: pointer;
  }

  /* Messages Body */
  .op-voice-body {
    flex: 1;
    overflow-y: auto;
    padding: 14px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    background: #FFFBF5;
    scroll-behavior: smooth;
  }

  .op-msg {
    padding: 12px 14px;
    border-radius: 14px;
    font-size: 13.5px;
    line-height: 1.55;
    max-width: 90%;
    word-break: break-word;
    animation: op-fade-in 0.25s ease-out;
  }
  @keyframes op-fade-in {
    from { opacity: 0; transform: translateY(6px); }
    to { opacity: 1; transform: translateY(0); }
  }

  .op-msg.pandit {
    background: #FFF3E0;
    color: #3E2723;
    border: 1px solid #FFE0B2;
    align-self: flex-start;
    border-bottom-left-radius: 4px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.04);
  }
  .op-msg.devotee {
    background: linear-gradient(135deg, #FF7043 0%, #F4511E 100%);
    color: white;
    align-self: flex-end;
    border-bottom-right-radius: 4px;
    box-shadow: 0 3px 8px rgba(244, 81, 30, 0.3);
  }

  /* Card Containers inside Pandit's Message */
  .op-action-cards {
    margin-top: 10px;
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  /* 1. Recommended Puja Card */
  .op-card-puja {
    background: #FFFFFF;
    border: 1.5px solid #FFB74D;
    border-radius: 12px;
    padding: 11px;
    box-shadow: 0 3px 10px rgba(255, 152, 0, 0.12);
  }
  .op-card-puja .title {
    font-weight: 700;
    color: #BF360C;
    font-size: 13.5px;
    margin-bottom: 3px;
  }
  .op-card-puja .details {
    font-size: 11.5px;
    color: #6D4C41;
    margin-bottom: 8px;
  }
  .op-card-puja .book-btn {
    display: block;
    text-align: center;
    background: linear-gradient(135deg, #FF6F00 0%, #E65100 100%);
    color: white !important;
    padding: 7px 12px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 700;
    font-size: 12.5px;
    transition: transform 0.2s;
  }

  /* 2. Gemstone Card */
  .op-card-gemstone {
    background: #F1F8E9;
    border: 1.5px solid #AED581;
    border-radius: 10px;
    padding: 9px 11px;
    font-size: 12px;
    color: #33691E;
  }
  .op-card-gemstone strong {
    color: #1B5E20;
    display: block;
    margin-bottom: 2px;
  }

  /* 3. Mantra Card */
  .op-card-mantra {
    background: #EDE7F6;
    border: 1px dashed #9575CD;
    border-radius: 10px;
    padding: 8px 11px;
    font-size: 12px;
    color: #4A148C;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
  }
  .op-card-mantra .mantra-text {
    flex: 1;
  }
  .op-card-mantra .mantra-listen-btn {
    background: #7E57C2;
    color: white;
    border: none;
    border-radius: 14px;
    padding: 3px 8px;
    font-size: 11px;
    cursor: pointer;
    white-space: nowrap;
    font-weight: 600;
  }

  /* 4. Astrologer Live CTA */
  .op-card-astro {
    background: #FFFDE7;
    border: 1px solid #FFF176;
    border-radius: 10px;
    padding: 8px 11px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 12px;
    color: #F57F17;
  }
  .op-card-astro a {
    background: #FBC02D;
    color: #212121 !important;
    padding: 4px 10px;
    border-radius: 6px;
    font-weight: 700;
    text-decoration: none;
    font-size: 11.5px;
  }

  /* 5. WhatsApp Share Button */
  .op-share-wa-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #25D366;
    color: white !important;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 11.5px;
    text-decoration: none;
    font-weight: 700;
    margin-top: 4px;
    align-self: flex-start;
  }

  /* Quick Suggestion Chips */
  .op-quick-chips {
    display: flex;
    gap: 6px;
    overflow-x: auto;
    padding: 8px 12px;
    background: #FFF8E1;
    border-top: 1px solid #FFE082;
    scrollbar-width: thin;
  }
  .op-quick-chips button {
    background: white;
    border: 1px solid #FFCC80;
    border-radius: 20px;
    padding: 5px 12px;
    font-size: 12px;
    color: #D84315;
    white-space: nowrap;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.2s;
  }
  .op-quick-chips button:hover {
    background: #FFE082;
    color: #BF360C;
  }

  /* Input Footer */
  .op-voice-footer {
    padding: 10px 12px;
    background: white;
    border-top: 1px solid #FFE0B2;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .op-voice-footer input {
    flex: 1;
    border: 1px solid #FFCC80;
    border-radius: 25px;
    padding: 8px 14px;
    font-size: 13.5px;
    outline: none;
    background: #FFFDF9;
    color: #333;
  }
  .op-voice-footer input:focus {
    border-color: #FF9800;
    background: #fff;
  }
  .op-mic-btn {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    border: none;
    background: #FF5722;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    transition: all 0.2s;
  }
  .op-mic-btn.listening {
    background: #E53935;
    animation: op-mic-pulse 1s infinite alternate;
  }
  @keyframes op-mic-pulse {
    from { transform: scale(1); box-shadow: 0 0 0 0 rgba(229, 57, 53, 0.7); }
    to { transform: scale(1.15); box-shadow: 0 0 0 8px rgba(229, 57, 53, 0); }
  }
  .op-send-btn {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    border: none;
    background: #FF6F00;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    transition: transform 0.2s;
  }
  .op-send-btn:hover {
    transform: scale(1.06);
  }

  @media (max-width: 576px) {
    #onlinepuja-voice-widget-btn {
      bottom: calc(env(safe-area-inset-bottom, 0px) + 18px);
      right: 14px;
      padding: 9px 15px;
      font-size: 13px;
    }
    #onlinepuja-voice-modal {
      bottom: calc(env(safe-area-inset-bottom, 0px) + 10px);
      right: 10px;
      left: 10px;
      width: calc(100vw - 20px);
      max-height: calc(100vh - 20px);
      height: 90vh;
      border-radius: 18px;
    }
  }

  /* When mobile sticky bar or fixed bottom bar is present, lift the voice widget cleanly */
  @media (max-width: 767.98px) {
    body:has(.fixed-bottom) #onlinepuja-voice-widget-btn,
    body:has(.onlinepuja-mobile-sticky-bar) #onlinepuja-voice-widget-btn,
    body.has-fixed-bottom #onlinepuja-voice-widget-btn {
      bottom: calc(env(safe-area-inset-bottom, 0px) + 68px) !important;
    }
    body:has(.fixed-bottom) #onlinepuja-voice-modal,
    body:has(.onlinepuja-mobile-sticky-bar) #onlinepuja-voice-modal,
    body.has-fixed-bottom #onlinepuja-voice-modal {
      bottom: calc(env(safe-area-inset-bottom, 0px) + 64px) !important;
      max-height: calc(100vh - env(safe-area-inset-bottom, 0px) - 80px) !important;
    }
  }
</style>

<!-- Floating Launcher Button -->
<div id="onlinepuja-voice-widget-btn" onclick="window.toggleOnlinePujaVoiceModal()">
  <span class="pulse-ring"></span>
  <span>🎙️ पंडित जी (OnlinePuja AI)</span>
</div>

<!-- Divine Assistant Popup Modal -->
<div id="onlinepuja-voice-modal">
  <!-- Header -->
  <div class="op-voice-header">
    <h3>
      <span>🕉️</span>
      <span>पंडित जी (OnlinePuja AI)</span>
    </h3>
    <div class="header-controls">
      <button id="op-audio-toggle-btn" class="header-btn" onclick="window.togglePanditAudio()" title="आवाज बंद / चालू करें">🔊</button>
      <button class="header-btn" onclick="window.clearPanditChat()" title="चैट रीसेट करें">🗑️</button>
      <button class="header-btn" onclick="window.toggleOnlinePujaVoiceModal()" title="बंद करें">✕</button>
    </div>
  </div>

  <!-- Kundali Details Strip -->
  <div class="op-kundali-strip">
    <span id="op-kundali-label">🔮 व्यक्तिगत कुंडली विश्लेषण जोड़ें</span>
    <button onclick="window.toggleKundaliDrawer()">+ विवरण</button>
  </div>

  <!-- Collapsible Kundali Drawer -->
  <div id="op-kundali-drawer">
    <div style="font-weight:700;color:#BF360C;margin-bottom:6px;">जन्म विवरण (Personal Vedic Profile)</div>
    <input type="text" id="op-user-name" placeholder="आपका नाम (Name)" />
    <input type="date" id="op-user-dob" placeholder="जन्म तिथि (DOB)" />
    <input type="time" id="op-user-tob" placeholder="जन्म समय (Birth Time)" />
    <input type="text" id="op-user-pob" placeholder="जन्म स्थान / शहर (Birth Place)" />
    <button class="save-btn" onclick="window.saveKundaliDetails()">सहेजें व कुंडली विश्लेषण शुरू करें</button>
  </div>

  <!-- Messages Body -->
  <div class="op-voice-body" id="op-voice-chat-stream">
    <div class="op-msg pandit">
      🙏 <strong>ॐ नमः शिवाय। आयुष्मान भव!</strong><br>
      मैं पंडित जी (OnlinePuja AI) हूँ। अपनी कुंडली, विवाह, व्यापार, स्वास्थ्य अथवा किसी भी ग्रह दोष के निवारण हेतु बोलकर या लिखकर पूछें।
    </div>
  </div>

  <!-- Quick Prompts Chips -->
  <div class="op-quick-chips" id="op-quick-chips-bar">
    <button onclick="window.sendVoiceQueryDirect('विवाह में बाधा और मंगल दोष के उपाय बताइए')">💍 विवाह में बाधा</button>
    <button onclick="window.sendVoiceQueryDirect('व्यापार वृद्धि एवं धन लाभ के सरल उपाय')">💼 व्यापार वृद्धि</button>
    <button onclick="window.sendVoiceQueryDirect('कालसर्प दोष और नवग्रह शांति के उपाय')">🐍 कालसर्प व ग्रह शांति</button>
    <button onclick="window.sendVoiceQueryDirect('आज का शुभ मुहूर्त और राहु काल क्या है?')">✨ आज का मुहूर्त</button>
    <button onclick="window.sendVoiceQueryDirect('स्वास्थ्य लाभ एवं महामृत्युंजय पूजा उपाय')">🌿 स्वास्थ्य रक्षा</button>
  </div>

  <!-- Input Bar with Mic & Send -->
  <div class="op-voice-footer">
    <button class="op-mic-btn" id="op-mic-toggle-btn" title="बोलकर पूछें (Click to Speak)" onclick="window.toggleSpeechRecognition()">
      🎤
    </button>
    <input type="text" id="op-voice-text-input" placeholder="पंडित जी से पूछें या माइक दबाएं..." onkeypress="if(event.key==='Enter') window.submitTextVoiceQuery()" />
    <button class="op-send-btn" onclick="window.submitTextVoiceQuery()" title="भेजें">
      ➤
    </button>
  </div>
</div>

<script>
  let opRecognition = null;
  let opIsListening = false;
  let opAudioEnabled = true;

  // Load saved Kundali profile
  function getSavedKundali() {
    try {
      return JSON.parse(localStorage.getItem('op_kundali_profile') || '{}');
    } catch (e) {
      return {};
    }
  }

  window.toggleKundaliDrawer = function() {
    const d = document.getElementById('op-kundali-drawer');
    if (!d) return;
    d.style.display = (d.style.display === 'block') ? 'none' : 'block';
  };

  window.saveKundaliDetails = function() {
    const name = document.getElementById('op-user-name').value.trim();
    const dob = document.getElementById('op-user-dob').value;
    const tob = document.getElementById('op-user-tob').value;
    const pob = document.getElementById('op-user-pob').value.trim();

    const profile = { name, dob, tob, pob };
    localStorage.setItem('op_kundali_profile', JSON.stringify(profile));

    const lbl = document.getElementById('op-kundali-label');
    if (lbl) lbl.innerText = `🔮 कुंडली सक्रिय: ${name || 'भक्त'} (${dob || ''})`;
    window.toggleKundaliDrawer();

    window.sendVoiceQueryDirect(`मेरी जन्म कुंडली के अनुसार गृह दशा एवं आगामी भविष्य बताइए`);
  };

  window.clearPanditChat = function() {
    if (window.speechSynthesis) window.speechSynthesis.cancel();
    const chatStream = document.getElementById('op-voice-chat-stream');
    if (chatStream) {
      chatStream.innerHTML = `
        <div class="op-msg pandit">
          🙏 <strong>ॐ नमः शिवाय। आयुष्मान भव!</strong><br>
          नवीन परामर्श प्रारंभ हो चुका है। अपनी किसी भी समस्या या मनोकामना का शास्त्रसम्मत समाधान पूछें।
        </div>
      `;
    }
  };

  window.togglePanditAudio = function() {
    opAudioEnabled = !opAudioEnabled;
    const btn = document.getElementById('op-audio-toggle-btn');
    if (btn) btn.innerText = opAudioEnabled ? '🔊' : '🔇';
    if (!opAudioEnabled && window.speechSynthesis) {
      window.speechSynthesis.cancel();
    }
  };

  window.toggleOnlinePujaVoiceModal = function() {
    const modal = document.getElementById('onlinepuja-voice-modal');
    const launcher = document.getElementById('onlinepuja-voice-widget-btn');
    if (!modal) return;

    if (modal.style.display === 'flex') {
      modal.style.display = 'none';
      if (launcher) launcher.style.display = 'flex';
      if (window.speechSynthesis) window.speechSynthesis.cancel();
    } else {
      modal.style.display = 'flex';
      if (launcher) launcher.style.display = 'none';

      // Check existing profile
      const prof = getSavedKundali();
      if (prof.name || prof.dob) {
        const lbl = document.getElementById('op-kundali-label');
        if (lbl) lbl.innerText = `🔮 कुंडली सक्रिय: ${prof.name || 'भक्त'} (${prof.dob || ''})`;
      }

      setTimeout(() => {
        const inp = document.getElementById('op-voice-text-input');
        if (inp) inp.focus();
      }, 200);
    }
  };

  window.initOpSpeechRecognition = function() {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SpeechRecognition) {
      console.warn("Web Speech API not supported on this browser.");
      return null;
    }
    const rec = new SpeechRecognition();
    rec.continuous = false;
    rec.interimResults = false;
    rec.lang = 'hi-IN';

    rec.onstart = function() {
      opIsListening = true;
      const btn = document.getElementById('op-mic-toggle-btn');
      if (btn) btn.classList.add('listening');
      const input = document.getElementById('op-voice-text-input');
      if (input) input.placeholder = "पंडित जी सुन रहे हैं... बोलिए...";
    };

    rec.onresult = function(event) {
      const transcript = event.results[0][0].transcript;
      const input = document.getElementById('op-voice-text-input');
      if (input) input.value = transcript;
      window.submitTextVoiceQuery();
    };

    rec.onerror = function(err) {
      console.warn("Speech error:", err);
      window.stopListeningUI();
    };

    rec.onend = function() {
      window.stopListeningUI();
    };

    return rec;
  };

  window.stopListeningUI = function() {
    opIsListening = false;
    const btn = document.getElementById('op-mic-toggle-btn');
    if (btn) btn.classList.remove('listening');
    const input = document.getElementById('op-voice-text-input');
    if (input) input.placeholder = "पंडित जी से पूछें या माइक दबाएं...";
  };

  window.toggleSpeechRecognition = function() {
    if (!opRecognition) opRecognition = window.initOpSpeechRecognition();
    if (!opRecognition) {
      alert("आपके डिवाइस पर वॉइस इनपुट समर्थित नहीं है। कृपया लिखकर पूछें।");
      return;
    }

    if (opIsListening) {
      opRecognition.stop();
      window.stopListeningUI();
    } else {
      if (window.speechSynthesis) window.speechSynthesis.cancel();
      try {
        opRecognition.start();
      } catch (e) {
        opRecognition.stop();
        setTimeout(() => opRecognition.start(), 200);
      }
    }
  };

  window.sendVoiceQueryDirect = function(promptText) {
    const input = document.getElementById('op-voice-text-input');
    if (input) input.value = promptText;
    window.submitTextVoiceQuery();
  };

  window.updateFollowupChips = function(chips) {
    if (!chips || !chips.length) return;
    const bar = document.getElementById('op-quick-chips-bar');
    if (!bar) return;
    bar.innerHTML = '';
    chips.forEach(chipText => {
      const btn = document.createElement('button');
      btn.innerText = chipText;
      btn.onclick = () => window.sendVoiceQueryDirect(chipText);
      bar.appendChild(btn);
    });
  };

  // Client-side intelligent Vedic fallback engine (100% offline & load resilient)
  function getClientVedicFallback(query, prof) {
    const q = (query || '').toLowerCase();
    const name = prof.name ? prof.name : 'भक्त';
    const dobNote = prof.dob ? ` (जन्म विवरण: ${prof.dob})` : '';

    if (/(व्यापार|धन|पैसा|नौकरी|बिजनेस|घाटा|business|career|job|money|loss|कर्ज|income|काम|रोजगार|दुकान|बिक्री)/i.test(q)) {
      return {
        reply_text: `🙏 ॐ गं गणपतये नमः! आयुष्मान भव ${name}। व्यापार एवं आर्थिक उन्नति हेतु बुध व बृहस्पति की अनुकूलता आवश्यक है।${dobNote}\n\n✨ अचूक शास्त्रोक्त उपाय:\n१. प्रत्येक बुधवार को श्री गणेश जी को २१ दूर्वा व बेसन के लड्डू अर्पित करें।\n२. व्यापार स्थल पर नित्य कनकधारा स्तोत्र अथवा श्री सूक्त का पाठ करें।\n३. गौमाता को हरा चारा व गुड़ खिलाएं।\n४. शनिवार की शाम व्यावसायिक प्रतिष्ठान में गुग्गल व लोबान की धूप दें।`,
        recommended_puja: {
          title: "Shree Lakshmi Kubera Maha Dhan Labh Puja",
          place: "महालक्ष्मी मंदिर एवं काशी सिद्ध क्षेत्र",
          price: 2100,
          booking_url: "/puja-list"
        },
        recommended_gemstone: {
          name: "शुद्ध पन्ना (Emerald) अथवा कनकधारा यंत्र",
          metal: "स्वर्ण या चांदी की अंगूठी",
          wearing_day: "बुधवार प्रातः कनिष्ठिका अंगुली में",
          benefit: "व्यापारिक बुद्धि, धन आगमन एवं ऋण मुक्ति प्रदान करता है।"
        },
        mantra: "ॐ श्रीं ह्रीं क्लीं ग्लौं गं गणपतये वर वरद सर्वजनं मे वशमानय स्वाहा ॥",
        suggested_followups: [
          "🏪 व्यापार स्थल पर कौन सा यंत्र स्थापित करें?",
          "💰 कर्ज मुक्ति के अचूक उपाय",
          "✨ नया व्यापार शुरू करने का शुभ मुहूर्त?",
          "📞 वरिष्ठ ज्योतिषी से करियर परामर्श लें"
        ]
      };
    } else if (/(रोग|बीमारी|स्वास्थ्य|health|pain|illness|मृत्युंजय|आयु|तबीयत|कष्ट|अस्पताल|दवा)/i.test(q)) {
      return {
        reply_text: `🙏 ॐ नमः शिवाय! कल्याणमस्तु ${name}। स्वास्थ्य रक्षा एवं दीर्घायु हेतु भगवान शिव की आराधना सर्वोपरि है।${dobNote}\n\n✨ स्वास्थ्य लाभ उपाय:\n१. प्रतिदिन प्रातः महामृत्युंजय मंत्र का कम से कम ११ अथवा १०८ बार जप करें।\n२. सोमवार को शिवलिंग पर जल और बेलपत्र अर्पित करें।\n३. रोगियों की सेवा करें तथा यथाशक्ति औषध दान करें।`,
        recommended_puja: {
          title: "Maha Mrityunjaya Arogya & Ayushya Maha Yagya",
          place: "महाकालेश्वर उज्जैन / त्र्यंबकेश्वर",
          price: 2500,
          booking_url: "/puja-list"
        },
        recommended_gemstone: {
          name: "सिद्ध पंचमुखी रुद्राक्ष अथवा पारद शिवलिंग",
          metal: "लाल धागे अथवा चांदी में",
          wearing_day: "सोमवार प्रातः शिवलिंग पर स्पर्श कराकर",
          benefit: "अकाल मृत्यु भय व शारीरिक व्याधियों से अचूक सुरक्षा।"
        },
        mantra: "ॐ त्र्यम्बकं यजामहे सुगन्धिं पुष्टिवर्धनम्। उर्वारुकमिव बन्धनान्मृत्योर्मुक्षीय माऽमृतात्॥",
        suggested_followups: [
          "🔱 महामृत्युंजय मंत्र जप की विधि?",
          "🌿 आरोग्य प्राप्ति हेतु सोमवार को क्या दान करें?",
          "📞 ज्योतिषी से रोग शांति मार्गदर्शन लें"
        ]
      };
    } else if (/(कालसर्प|राहु|केतु|शनि|shani|rahu|ketu|kaal\s*sarp|sadhesati|साढ़ेसाती|ढैय्या)/i.test(q)) {
      return {
        reply_text: `🙏 ॐ नमः शिवाय! हर हर महादेव ${name}। राहु, केतु अथवा शनि के प्रभाव से जीवन में संघर्ष उत्पन्न होता है।${dobNote}\n\n✨ ग्रह शांति उपाय:\n१. शनिवार को पीपल के नीचे सरसों के तेल का चौमुखा दीपक जलाएं।\n२. 'ॐ शं शनैश्चराय नमः' मंत्र का नित्य जप करें।\n३. काले तिल व उड़द की दाल का जरूरतमंदों को दान करें।`,
        recommended_puja: {
          title: "Vedic Navagraha & Kaal Sarp Dosh Shanti Maha Puja",
          place: "त्र्यंबकेश्वर एवं उज्जैन सिद्ध तीर्थ",
          price: 2100,
          booking_url: "/puja-list"
        },
        recommended_gemstone: {
          name: "नीलमणि (Amethyst) अथवा अष्टमुखी रुद्राक्ष",
          metal: "पंचधातु या लोहे का छल्ला",
          wearing_day: "शनिवार सायं मध्यमा अंगुली में",
          benefit: "शनि की साढ़ेसाती व राहु-केतु के दुष्प्रभावों का शमन।"
        },
        mantra: "ॐ शं शनैश्चराय नमः ॥ एवं ॐ रां राहवे नमः ॥",
        suggested_followups: [
          "🐍 कालसर्प दोष के मुख्य लक्षण क्या हैं?",
          "🪐 साढ़ेसाती से बचने के सरल घरेलू उपाय",
          "📞 कुंडली में ग्रह स्थिति की जांच करवाएं"
        ]
      };
    } else if (/(विवाह|शादी|मंगल|दोष|रिश्ता|लड़की|लड़का|सगाई|marriage|mangal|vivah|match|manglik)/i.test(q)) {
      return {
        reply_text: `🙏 ॐ नमः शिवाय! आयुष्मान भव ${name}। विवाह में विलंब अथवा बाधा मंगल दोष अथवा गुरु-शुक्र की दुर्बलता से उत्पन्न होती है।${dobNote}\n\n✨ अचूक वैदिक उपाय:\n१. प्रत्येक मंगलवार को हनुमान जी के समक्ष चमेली के तेल का दीपक जलाकर हनुमान चालीसा पढ़ें।\n२. नित्य प्रातः शिवलिंग पर कच्चा दूध व केसर मिश्रित जल अर्पित करें।\n३. मंगलवार को तांबे के पात्र अथवा लाल वस्त्र का दान करें।`,
        recommended_puja: {
          title: "Tulsi Vivah & Mangal Dosh Nivaran Maha Puja",
          place: "उज्जैन मंगलनाथ सिद्ध तीर्थ",
          price: 2100,
          booking_url: "/puja-list"
        },
        recommended_gemstone: {
          name: "त्रिकोणीय रक्त मूंगा (Italian Red Coral)",
          metal: "तांबे अथवा अष्टधातु की अंगूठी",
          wearing_day: "मंगलवार प्रातः अनामिका अंगुली में",
          benefit: "मंगल के उग्र प्रभाव को शांत कर शीघ्र विवाह का योग बनाता है।"
        },
        mantra: "ॐ क्रां क्रीं क्रौं सः भौमाय नमः ॥ अथवा ॐ कात्यायनि महामाये नमः ॥",
        suggested_followups: [
          "📿 मंगल दोष शांति का सिद्ध मंत्र?",
          "💍 क्या मुझे मूंगा पहनना चाहिए?",
          "📞 ज्योतिषी से कुंडली मिलान कराएं",
          "✨ विवाह के शुभ मुहूर्त कब हैं?"
        ]
      };
    } else {
      return {
        reply_text: `🙏 ॐ नमः शिवाय! आयुष्मान भव ${name}। आपके प्रश्न का शास्त्रसम्मत वैदिक समाधान उपलब्ध है।${dobNote}\n\n✨ नित्य कल्याणकारी उपाय:\n१. प्रतिदिन सूर्योदय के समय तांबे के लोटे से सूर्य देव को जल अर्पित करें।\n२. 'ॐ नमः शिवाय' महामंत्र का नियमित रुद्राक्ष माला से जप करें।\n३. गौमाता को पहली रोटी खिलाएं और बुजुर्गों का आशीर्वाद लें।`,
        recommended_puja: {
          title: "Vedic Sarva Karya Siddhi Maha Puja",
          place: "काशी विश्वनाथ एवं विंध्याचल पीठ",
          price: 1500,
          booking_url: "/puja-list"
        },
        recommended_gemstone: {
          name: "सिद्ध पंचमुखी रुद्राक्ष",
          metal: "चांदी या लाल रेशमी धागे में",
          wearing_day: "सोमवार प्रातः शिवलिंग पर स्पर्श कराकर",
          benefit: "मनोकामना पूर्ति एवं सकारात्मक ऊर्जा का संचार।"
        },
        mantra: "ॐ नमो भगवते वासुदेवाय ॥",
        suggested_followups: [
          "💍 विवाह में बाधा के उपाय",
          "💼 व्यापार व नौकरी में उन्नति",
          "✨ आज का शुभ मुहूर्त",
          "📞 वरिष्ठ ज्योतिषी से बात करें (₹15/min)"
        ]
      };
    }
  }

  // Render assistant message and interactive cards
  function renderPanditResponse(data) {
    const chatStream = document.getElementById('op-voice-chat-stream');
    const panditReply = data.reply_text || '🙏 ॐ नमः शिवाय! आयुष्मान भव।';
    const panditMsg = document.createElement('div');
    panditMsg.className = 'op-msg pandit';
    panditMsg.innerHTML = panditReply.replace(/\n/g, '<br>');

    const cardsContainer = document.createElement('div');
    cardsContainer.className = 'op-action-cards';

    // A. Recommended Puja Card
    if (data.recommended_puja) {
      const puja = data.recommended_puja;
      const pujaCard = document.createElement('div');
      pujaCard.className = 'op-card-puja';
      pujaCard.innerHTML = `
        <div class="title">🔱 ${puja.title}</div>
        <div class="details">📍 ${puja.place} • दक्षिणा: ₹${puja.price}</div>
        <a href="${puja.booking_url}" class="book-btn" target="_blank">🙏 संकल्प लें व पूजा बुक करें</a>
      `;
      cardsContainer.appendChild(pujaCard);
    }

    // B. Auspicious Gemstone Card
    if (data.recommended_gemstone) {
      const gem = data.recommended_gemstone;
      const gemCard = document.createElement('div');
      gemCard.className = 'op-card-gemstone';
      gemCard.innerHTML = `
        <strong>💎 रत्न उपाय: ${gem.name} (${gem.metal})</strong>
        <div>${gem.wearing_day} — ${gem.benefit}</div>
      `;
      cardsContainer.appendChild(gemCard);
    }

    // C. Sacred Vedic Mantra with Audio Listen
    if (data.mantra) {
      const mantraCard = document.createElement('div');
      mantraCard.className = 'op-card-mantra';
      mantraCard.innerHTML = `
        <div class="mantra-text"><strong>📿 सिद्ध मंत्र जप:</strong> ${data.mantra}</div>
        <button class="mantra-listen-btn" onclick="window.speakPanditVoice('${data.mantra.replace(/'/g, "\\'")}')">🔊 सुनें</button>
      `;
      cardsContainer.appendChild(mantraCard);
    }

    // D. 1-on-1 Astrologer Live Consultation CTA
    const astroCard = document.createElement('div');
    astroCard.className = 'op-card-astro';
    astroCard.innerHTML = `
      <span>📞 व्यक्तिगत विस्तृत फलादेश हेतु:</span>
      <a href="/astrologers" target="_blank">ज्योतिषी से बात करें</a>
    `;
    cardsContainer.appendChild(astroCard);

    // E. WhatsApp Share Button
    const waUrl = data.whatsapp_share_url || `https://api.whatsapp.com/send?text=${encodeURIComponent('🙏 पंडित जी (OnlinePuja AI) द्वारा सुझाए गए वैदिक उपाय:\n\n' + panditReply.substring(0, 300) + '...\nपूरा समाधान देखें: ' + window.location.origin)}`;
    const shareBtn = document.createElement('a');
    shareBtn.className = 'op-share-wa-btn';
    shareBtn.href = waUrl;
    shareBtn.target = '_blank';
    shareBtn.innerHTML = `📲 व्हाट्सएप पर उपाय सेव करें`;
    cardsContainer.appendChild(shareBtn);

    panditMsg.appendChild(cardsContainer);
    chatStream.appendChild(panditMsg);
    chatStream.scrollTop = chatStream.scrollHeight;

    // Update follow-up chips dynamically
    if (data.suggested_followups) {
      window.updateFollowupChips(data.suggested_followups);
    }

    // Speak aloud in sweet Hindi voice
    if (opAudioEnabled) {
      window.speakPanditVoice(panditReply);
    }
  }

  window.submitTextVoiceQuery = function() {
    const input = document.getElementById('op-voice-text-input');
    const query = (input ? input.value : '').trim();
    if (!query) return;

    input.value = '';
    window.stopListeningUI();

    // 1. Render Devotee Message
    const chatStream = document.getElementById('op-voice-chat-stream');
    const devoteeMsg = document.createElement('div');
    devoteeMsg.className = 'op-msg devotee';
    devoteeMsg.innerText = query;
    chatStream.appendChild(devoteeMsg);
    chatStream.scrollTop = chatStream.scrollHeight;

    // 2. Render Loading Indicator
    const loaderMsg = document.createElement('div');
    loaderMsg.className = 'op-msg pandit';
    loaderMsg.id = 'op-current-loading-msg';
    loaderMsg.innerHTML = '✨ <em>पंडित जी विचार कर रहे हैं...</em>';
    chatStream.appendChild(loaderMsg);
    chatStream.scrollTop = chatStream.scrollHeight;

    // 3. Dispatch to OnlinePuja Brain API
    const prof = getSavedKundali();
    const payload = {
      message: query,
      name: prof.name || '',
      dob: prof.dob || '',
      tob: prof.tob || '',
      pob: prof.pob || ''
    };

    const controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
    const timeoutTimer = controller ? setTimeout(() => controller.abort(), 12000) : null;

    fetch('/api/brain/voice/consult', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      signal: controller ? controller.signal : undefined,
      body: JSON.stringify(payload)
    })
    .then(r => {
      if (timeoutTimer) clearTimeout(timeoutTimer);
      if (!r.ok) throw new Error('HTTP ' + r.status);
      return r.json();
    })
    .then(res => {
      const loader = document.getElementById('op-current-loading-msg');
      if (loader) loader.remove();

      if (res && res.success && res.data) {
        renderPanditResponse(res.data);
      } else {
        const fallback = getClientVedicFallback(query, prof);
        renderPanditResponse(fallback);
      }
    })
    .catch(err => {
      if (timeoutTimer) clearTimeout(timeoutTimer);
      console.warn('Pandit AI Network Notice, activating intelligent Vedic client engine:', err);
      const loader = document.getElementById('op-current-loading-msg');
      if (loader) loader.remove();

      // Zero broken fallback: calculate topic-specific Vedic answer instantly
      const fallback = getClientVedicFallback(query, prof);
      renderPanditResponse(fallback);
    });
  };

  window.speakPanditVoice = function(text) {
    if (!opAudioEnabled || !window.speechSynthesis) return;
    window.speechSynthesis.cancel();

    const cleanSpeech = text
      .replace(/[*#_~`]/g, '')
      .replace(/ID\s*\d+/g, '')
      .replace(/https?:\/\/\S+/g, '')
      .replace(/🌺|🙏|🔱|🪔|✨|📍|💎|📿|📞|📲|🚗|💍|💼|🐍|🌿/g, '');

    const utterance = new SpeechSynthesisUtterance(cleanSpeech);
    utterance.lang = 'hi-IN';
    utterance.rate = 0.92;
    utterance.pitch = 0.98;

    const voices = window.speechSynthesis.getVoices();
    const hindiVoice = voices.find(v => (v.lang && v.lang.includes('hi')) || (v.name && v.name.includes('Hindi')));
    if (hindiVoice) utterance.voice = hindiVoice;

    window.speechSynthesis.speak(utterance);
  };

  // Auto-detect sticky bottom bars on mobile and adjust widget position
  function adjustOnlinePujaWidgetPosition() {
    const fixedBar = document.querySelector('.fixed-bottom:not(#onlinepuja-voice-modal):not(#onlinepuja-voice-widget-btn), .onlinepuja-mobile-sticky-bar');
    const btn = document.getElementById('onlinepuja-voice-widget-btn');
    const modal = document.getElementById('onlinepuja-voice-modal');
    if (!btn) return;

    if (window.innerWidth <= 768 && fixedBar && window.getComputedStyle(fixedBar).display !== 'none') {
      document.body.classList.add('has-fixed-bottom');
      const barHeight = fixedBar.offsetHeight || 56;
      btn.style.setProperty('bottom', `calc(env(safe-area-inset-bottom, 0px) + ${barHeight + 12}px)`, 'important');
      if (modal) {
        modal.style.setProperty('bottom', `calc(env(safe-area-inset-bottom, 0px) + ${barHeight + 8}px)`, 'important');
      }
    } else {
      document.body.classList.remove('has-fixed-bottom');
      btn.style.bottom = '';
      if (modal) modal.style.bottom = '';
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', adjustOnlinePujaWidgetPosition);
  } else {
    adjustOnlinePujaWidgetPosition();
  }
  window.addEventListener('resize', adjustOnlinePujaWidgetPosition);
</script>
