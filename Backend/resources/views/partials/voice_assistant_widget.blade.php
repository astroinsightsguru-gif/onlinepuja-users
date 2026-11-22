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
    width: 400px;
    max-width: calc(100vw - 32px);
    height: 590px;
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
    font-family: inherit;
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
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 11.5px;
    text-decoration: none;
    font-weight: 700;
    margin-top: 6px;
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
      bottom: 18px;
      right: 14px;
      padding: 9px 15px;
      font-size: 13px;
    }
    #onlinepuja-voice-modal {
      bottom: 10px;
      right: 10px;
      left: 10px;
      width: calc(100vw - 20px);
      max-height: calc(100vh - 20px);
      height: 90vh;
      border-radius: 18px;
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

    // 3. Dispatch to OnlinePuja Brain API with fast timeout
    const prof = getSavedKundali();
    const payload = {
      message: query,
      name: prof.name || '',
      dob: prof.dob || '',
      tob: prof.tob || '',
      pob: prof.pob || ''
    };

    fetch('/api/brain/voice/consult', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(res => {
      const loader = document.getElementById('op-current-loading-msg');
      if (loader) loader.remove();

      if (res && res.success && res.data) {
        const data = res.data;
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

        // C. Sacred Vedic Mantra
        if (data.mantra) {
          const mantraCard = document.createElement('div');
          mantraCard.className = 'op-card-mantra';
          mantraCard.innerHTML = `<strong>📿 सिद्ध मंत्र जप:</strong> ${data.mantra}`;
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
        if (data.whatsapp_share_url) {
          const shareBtn = document.createElement('a');
          shareBtn.className = 'op-share-wa-btn';
          shareBtn.href = data.whatsapp_share_url;
          shareBtn.target = '_blank';
          shareBtn.innerHTML = `📲 व्हाट्सएप पर उपाय सेव करें`;
          cardsContainer.appendChild(shareBtn);
        }

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
      } else {
        const errMsg = document.createElement('div');
        errMsg.className = 'op-msg pandit';
        errMsg.innerText = '🙏 क्षमा करें, संपर्क में व्यवधान आया। कृपया पुनः प्रयास करें।';
        chatStream.appendChild(errMsg);
        chatStream.scrollTop = chatStream.scrollHeight;
      }
    })
    .catch(err => {
      console.error('Pandit AI Error:', err);
      const loader = document.getElementById('op-current-loading-msg');
      if (loader) loader.remove();
      const errMsg = document.createElement('div');
      errMsg.className = 'op-msg pandit';
      errMsg.innerHTML = '🙏 <strong>ॐ नमः शिवाय।</strong><br>प्रभु कृपा से समस्त कष्ट दूर होंगे। विस्तृत समाधान हेतु पुनः प्रयास करें या ज्योतिषी से परामर्श लें।';
      chatStream.appendChild(errMsg);
      chatStream.scrollTop = chatStream.scrollHeight;
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
</script>
