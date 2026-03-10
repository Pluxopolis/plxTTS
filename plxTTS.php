<?php
if (!defined('PLX_ROOT')) exit;

/**
 * Plugin plxTTS — Lecteur Text-to-Speech pour PluXml
 *
 * - Lecteur fixe sur le <body>, activé par IntersectionObserver
 * - Réglages voix/vitesse/pitch partagés, mémorisés localStorage
 * - Mode dyslexie : police OpenDyslexic sur les <article>
 * - Panneau réglages ancré au lecteur fixe (toujours dans le viewport)
 * - Cible  : balise <article> (sans classe ni id)
 * - Langue : attribut lang de <html>
 * - Hooks  : ThemeEndHead (CSS) + ThemeEndBody (JS)
 *
 * Polices dans plugins/plxTTS/fonts/ — voir fonts/README.txt
 */
class plxTTS extends plxPlugin {

    const BEGIN_CODE = '<?php' . PHP_EOL;
    const END_CODE   = PHP_EOL . '?>';

    public $intro = true; /* true = page de liste (chapo), false = article/statique complet */

    public function __construct($default_lang) {
        parent::__construct($default_lang);
        $this->addHook('ThemeEndHead', 'ThemeEndHead');
        $this->addHook('ThemeEndBody', 'ThemeEndBody');
        $this->addHook('plxMotorDemarrageEnd', 'plxMotorDemarrageEnd');
    }

    public function plxMotorDemarrageEnd() {
        /* Détecter le mode — article/statique = contenu complet, sinon chapo */
        echo self::BEGIN_CODE;
        ?>
        if($this->mode == 'article' || $this->mode == 'static') {
            $this->plxPlugins->aPlugins["<?= __CLASS__ ?>"]->intro = false;
        }
        <?php
        echo self::END_CODE;
    }

    public function ThemeEndHead() {
        $fontsDir = PLX_ROOT . 'plugins/plxTTS/fonts/';
        $fontsUrl = PLX_PLUGINS . 'plxTTS/fonts/';
        $hasFonts = file_exists($fontsDir . 'OpenDyslexic-Regular.woff2');

        echo '<style>';
        if ($hasFonts) { echo "
@font-face {
  font-family:'OpenDyslexic';
  src:url('{$fontsUrl}OpenDyslexic-Regular.woff2') format('woff2');
  font-weight:normal; font-style:normal; font-display:swap;
}
@font-face {
  font-family:'OpenDyslexic';
  src:url('{$fontsUrl}OpenDyslexic-Bold.woff2') format('woff2');
  font-weight:bold; font-style:normal; font-display:swap;
}
@font-face {
  font-family:'OpenDyslexic';
  src:url('{$fontsUrl}OpenDyslexic-Italic.woff2') format('woff2');
  font-weight:normal; font-style:italic; font-display:swap;
}
"; }
        echo <<<'CSS'

/* ═══════════════════════════════════════════
   plxTTS — Barre fixe + Panneaux
   ═══════════════════════════════════════════ */

/* ── Barre fixe (body) ── */
#plx-tts-bar {
  position: fixed;
  bottom: 24px;
  left: 50%;
  transform: translateX(-50%) translateY(20px);
  z-index: 9999;
  display: flex;
  flex-direction: column;
  align-items: stretch;
  gap: 0;
  padding: 0;
  background: rgba(30,30,40,.92);
  color: #e8e8ee;
  border: 1px solid rgba(255,255,255,.12);
  border-radius: 16px;
  box-shadow: 0 4px 24px rgba(0,0,0,.35);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  font-family: inherit;
  font-size: .82rem;
  opacity: 0;
  pointer-events: none;
  transition: opacity .3s ease, transform .3s ease;
  white-space: nowrap;
  min-width: 240px;
}
#plx-tts-bar.plx-visible {
  opacity: 1;
  pointer-events: auto;
  transform: translateX(-50%) translateY(0);
}

/* ── Ligne titre (au-dessus des contrôles) ── */
#plx-tts-title-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  padding: 6px 12px 5px;
  border-bottom: 1px solid rgba(255,255,255,.08);
}
#plx-tts-title {
  font-size: .72rem;
  opacity: .65;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  flex: 1;
  font-style: italic;
}
#plx-tts-btn-theme {
  font-size: .75rem;
  line-height: 1;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  flex-shrink: 0;
  border: 1px solid rgba(255,255,255,.15);
  background: rgba(255,255,255,.06);
  cursor: pointer;
  color: inherit;
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: .7;
  transition: opacity .2s;
  padding: 0;
}
#plx-tts-btn-theme:hover { opacity: 1; }

/* ── Ligne contrôles ── */
#plx-tts-controls {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
}

/* ── Thème clair ── */
#plx-tts-bar.plx-theme-light {
  background: rgba(250,250,252,.96);
  color: #222;
  border-color: rgba(0,0,0,.12);
  box-shadow: 0 4px 24px rgba(0,0,0,.15);
}
#plx-tts-bar.plx-theme-light #plx-tts-title-row {
  border-bottom-color: rgba(0,0,0,.08);
}#plx-tts-bar.plx-theme-light .plx-tts-btn {
  background: rgba(0,0,0,.06);
  border-color: rgba(0,0,0,.14);
}
#plx-tts-bar.plx-theme-light .plx-tts-btn:hover:not(:disabled) {
  background: rgba(0,0,0,.11);
}
#plx-tts-bar.plx-theme-light .plx-tts-btn.plx-active {
  background: rgba(80,80,220,.12);
  border-color: rgba(80,80,220,.4);
}
#plx-tts-bar.plx-theme-light .plx-tts-sep {
  background: rgba(0,0,0,.12);
}
#plx-tts-bar.plx-theme-light .plx-tts-track {
  background: rgba(0,0,0,.1);
}
#plx-tts-bar.plx-theme-light .plx-tts-fill {
  background: rgba(80,80,220,.6);
}
#plx-tts-bar.plx-theme-light #plx-tts-btn-theme {
  border-color: rgba(0,0,0,.14);
  background: rgba(0,0,0,.04);
}
/* Panneaux thème clair */
#plx-tts-bar.plx-theme-light .plx-tts-panel {
  background: rgba(250,250,252,.98);
  color: #222;
  border-color: rgba(0,0,0,.12);
  box-shadow: 0 4px 24px rgba(0,0,0,.15);
}
#plx-tts-bar.plx-theme-light .plx-tts-panel .plx-tts-row select {
  background: rgba(0,0,0,.05);
  border-color: rgba(0,0,0,.14);
  color: #222;
}
#plx-tts-bar.plx-theme-light .plx-tts-panel .plx-tts-switch-track {
  background: rgba(0,0,0,.15);
}
#plx-tts-bar.plx-theme-light .plx-tts-panel .plx-tts-row input[type=range] {
  accent-color: rgba(80,80,220,.8);
}

/* ── Boutons ── */
.plx-tts-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 30px;
  padding: 0;
  background: rgba(255,255,255,.08);
  border: 1px solid rgba(255,255,255,.14);
  border-radius: 50%;
  cursor: pointer;
  color: inherit;
  opacity: .75;
  transition: opacity .2s, background .2s, border-color .2s;
  flex-shrink: 0;
}
.plx-tts-btn:hover:not(:disabled) {
  opacity: 1;
  background: rgba(255,255,255,.15);
}
.plx-tts-btn:disabled { opacity: .25; cursor: default; }
.plx-tts-btn.plx-active {
  opacity: 1;
  background: rgba(130,130,255,.25);
  border-color: rgba(130,130,255,.5);
}

/* Bouton dyslexie : texte Aa en OpenDyslexic */
#plx-tts-btn-dys {
  font-family: 'OpenDyslexic', sans-serif;
  font-size: .72rem;
  font-weight: bold;
  letter-spacing: -.02em;
  width: auto;
  padding: 0 8px;
  border-radius: 14px;
}

/* Séparateur vertical */
.plx-tts-sep {
  width: 1px;
  height: 18px;
  background: rgba(255,255,255,.15);
  flex-shrink: 0;
  transition: background .2s;
}

/* ── Barre de progression ── */
.plx-tts-track {
  width: 90px;
  height: 3px;
  background: rgba(255,255,255,.15);
  border-radius: 2px;
  overflow: hidden;
  flex-shrink: 0;
}
.plx-tts-fill {
  height: 100%;
  width: 0%;
  background: rgba(160,160,255,.8);
  border-radius: 2px;
  transition: width .3s ease;
}

/* ── Compteur ── */
.plx-tts-counter {
  font-size: .68rem;
  opacity: .5;
  font-variant-numeric: tabular-nums;
  min-width: 36px;
}

/* ── Panneaux (settings + dyslexie) — enfants absolus de la barre ── */
.plx-tts-panel {
  display: none;
  flex-direction: column;
  gap: 10px;
  position: absolute;
  bottom: calc(100% + 8px);  /* juste au-dessus de la barre */
  left: 50%;
  transform: translateX(-50%);
  z-index: 1;
  min-width: 260px;
  padding: 14px 16px;
  background: rgba(30,30,40,.96);
  color: #e8e8ee;
  border: 1px solid rgba(255,255,255,.12);
  border-radius: 12px;
  box-shadow: 0 4px 24px rgba(0,0,0,.4);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  font-size: .82rem;
}
.plx-tts-panel.plx-open { display: flex; }

.plx-tts-panel-title {
  font-size: .68rem;
  text-transform: uppercase;
  letter-spacing: .07em;
  opacity: .45;
  margin-bottom: 2px;
}

.plx-tts-row {
  display: flex;
  align-items: center;
  gap: 10px;
}
.plx-tts-row label {
  width: 72px;
  font-size: .72rem;
  opacity: .6;
  text-transform: uppercase;
  letter-spacing: .04em;
  flex-shrink: 0;
}
.plx-tts-row select {
  flex: 1;
  font-size: .78rem;
  font-family: inherit;
  background: rgba(255,255,255,.08);
  border: 1px solid rgba(255,255,255,.14);
  border-radius: 6px;
  color: inherit;
  padding: 4px 8px;
  cursor: pointer;
}
.plx-tts-row input[type=range] {
  flex: 1;
  accent-color: rgba(160,160,255,.9);
  cursor: pointer;
}
.plx-tts-val {
  font-size: .72rem;
  opacity: .55;
  width: 30px;
  text-align: right;
  flex-shrink: 0;
}

/* Toggle switch */
.plx-tts-toggle { display: flex; align-items: center; gap: 10px; }
.plx-tts-toggle > label { font-size: .82rem; cursor: pointer; opacity: .85; }
.plx-tts-switch {
  position: relative; width: 36px; height: 20px; flex-shrink: 0;
}
.plx-tts-switch input { opacity: 0; width: 0; height: 0; }
.plx-tts-switch-track {
  position: absolute; inset: 0;
  background: rgba(255,255,255,.15);
  border-radius: 20px; cursor: pointer;
  transition: background .2s;
}
.plx-tts-switch input:checked + .plx-tts-switch-track { background: rgba(80,200,120,.7); }
.plx-tts-switch-track::after {
  content: '';
  position: absolute; left: 2px; top: 2px;
  width: 16px; height: 16px;
  background: #fff; border-radius: 50%;
  transition: transform .2s;
  box-shadow: 0 1px 3px rgba(0,0,0,.3);
}
.plx-tts-switch input:checked + .plx-tts-switch-track::after { transform: translateX(16px); }

/* ── Mode dyslexie sur les articles ── */
article.plx-dyslexia-on {
  font-family: 'OpenDyslexic', sans-serif !important;
  line-height: 1.9 !important;
  letter-spacing: .04em !important;
  word-spacing: .12em !important;
}
article.plx-dyslexia-on * {
  font-family: 'OpenDyslexic', sans-serif !important;
}

/* ── Accessible uniquement aux lecteurs d'écran ── */
.plx-sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0,0,0,0);
  white-space: nowrap;
  border: 0;
}

/* ── Surlignage bloc en cours ── */
.plx-tts-active {
  background: rgba(130,130,255,.1);
  border-radius: 3px;
  outline: 2px solid rgba(130,130,255,.25);
  outline-offset: 3px;
  transition: background .25s, outline .25s;
  scroll-margin-top: 35vh;
}
CSS;
        echo '</style>';
    }

    public function ThemeEndBody() {

        $isIntro = $this->intro ? 'true' : 'false';
        $fontsDir = PLX_ROOT . 'plugins/plxTTS/fonts/';
        $hasFonts = file_exists($fontsDir . 'OpenDyslexic-Regular.woff2') ? 'true' : 'false';

        /* Chaînes traduites — échappement JS */
        $lBtnPlay       = json_encode($this->getLang('BTN_PLAY'));
        $lBtnPause      = json_encode($this->getLang('BTN_PAUSE'));
        $lBtnStop       = json_encode($this->getLang('BTN_STOP'));
        $lBtnPrev       = json_encode($this->getLang('BTN_PREV'));
        $lBtnNext       = json_encode($this->getLang('BTN_NEXT'));
        $lBtnSettings   = json_encode($this->getLang('BTN_SETTINGS'));
        $lBtnDyslexia   = json_encode($this->getLang('BTN_DYSLEXIA'));
        $lBtnTheme      = json_encode($this->getLang('BTN_THEME'));
        $lSettingsTitle = json_encode($this->getLang('SETTINGS_TITLE'));
        $lVoice         = json_encode($this->getLang('VOICE'));
        $lVoiceDefault  = json_encode($this->getLang('VOICE_DEFAULT'));
        $lRate          = json_encode($this->getLang('RATE'));
        $lPitch         = json_encode($this->getLang('PITCH'));
        $lDyslexiaTitle = json_encode($this->getLang('DYSLEXIA_TITLE'));
        $lDyslexiaLabel = json_encode($this->getLang('DYSLEXIA_LABEL'));
        $lPlayerRegion  = json_encode($this->getLang('PLAYER_REGION'));
        $lSettingsRegion= json_encode($this->getLang('SETTINGS_REGION'));
        $lDyslexiaRegion= json_encode($this->getLang('DYSLEXIA_REGION'));
        $lSkipLink      = json_encode($this->getLang('SKIP_LINK'));
        $lExcerpt       = json_encode($this->getLang('EXCERPT'));

        $svgPlay  = '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><polygon points="5 3 19 12 5 21 5 3"/></svg>';
        $svgPause = '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>';
        $svgStop  = '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="4" y="4" width="16" height="16" rx="2"/></svg>';
        $svgPrev  = '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><polygon points="19 20 9 12 19 4 19 20"/><line x1="5" y1="4" x2="5" y2="20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
        $svgNext  = '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><polygon points="5 4 15 12 5 20 5 4"/><line x1="19" y1="4" x2="19" y2="20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
        $svgGear  = '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>';

        echo <<<JS
<script>
(function () {
  'use strict';
  if (!window.speechSynthesis) return;

  var lang      = document.documentElement.lang || 'fr';
  var STORE     = 'plx-tts-settings';
  var SELECTORS = 'h1,h2,h3,h4,h5,h6,p,li,blockquote,figcaption';
  var hasFonts  = {$hasFonts};
  var isIntro   = {$isIntro};
  var lExcerpt  = {$lExcerpt};

  /* ══════════════════════
     Réglages partagés
     ══════════════════════ */
  var defaults = { voice:'', rate:1, pitch:1, dyslexia:false, theme:'dark' };
  function loadSettings() {
    try { return Object.assign({}, defaults, JSON.parse(localStorage.getItem(STORE)||'{}')); }
    catch(e) { return Object.assign({}, defaults); }
  }
  function saveSettings(s) {
    try { localStorage.setItem(STORE, JSON.stringify(s)); } catch(e) {}
  }
  var S = loadSettings();

  /* ══════════════════════
     Voix
     ══════════════════════ */
  var voices = [];
  function loadVoices() {
    var pfx = lang.split('-')[0].toLowerCase();
    voices = window.speechSynthesis.getVoices().filter(function(v){
      return v.lang.toLowerCase().startsWith(pfx);
    });
    document.querySelectorAll('.plx-voice-sel').forEach(fillVoiceSelect);
  }
  if (window.speechSynthesis.onvoiceschanged !== undefined)
    window.speechSynthesis.onvoiceschanged = loadVoices;
  loadVoices();

  function fillVoiceSelect(sel) {
    var cur = S.voice;
    sel.innerHTML = '<option value="">' + {$lVoiceDefault} + '</option>';
    voices.forEach(function(v){
      var o = document.createElement('option');
      o.value = v.name;
      o.textContent = v.name + ' (' + v.lang + ')';
      if (v.name === cur) o.selected = true;
      sel.appendChild(o);
    });
  }

  /* ══════════════════════
     Mode dyslexie
     ══════════════════════ */
  function applyDyslexia(on) {
    if (!hasFonts) return;
    document.querySelectorAll('article').forEach(function(a){
      a.classList.toggle('plx-dyslexia-on', on);
    });
    var btn = document.getElementById('plx-tts-btn-dys');
    if (btn) btn.classList.toggle('plx-active', on);
  }
  applyDyslexia(S.dyslexia);

  /* ══════════════════════
     Barre fixe (body)
     ══════════════════════ */
  var bar = document.createElement('div');
  bar.id = 'plx-tts-bar';
  bar.setAttribute('role', 'region');
  bar.setAttribute('aria-label', {$lPlayerRegion});
  if (S.theme === 'light') bar.classList.add('plx-theme-light');

  /* ── Ligne titre ── */
  var titleRow = document.createElement('div');
  titleRow.id = 'plx-tts-title-row';

  var titleSpan = document.createElement('p');
  titleSpan.id = 'plx-tts-title';
  titleSpan.setAttribute('aria-live', 'polite');
  titleSpan.setAttribute('aria-atomic', 'true');
  titleSpan.textContent = '';

  var btnTheme = document.createElement('button');
  btnTheme.id = 'plx-tts-btn-theme';
  btnTheme.setAttribute('type', 'button');
  btnTheme.setAttribute('aria-label', {$lBtnTheme});
  btnTheme.textContent = S.theme === 'light' ? '🌙' : '☀';
  btnTheme.addEventListener('click', function(e){
    e.stopPropagation();
    S.theme = S.theme === 'dark' ? 'light' : 'dark';
    saveSettings(S);
    var isLight = S.theme === 'light';
    bar.classList.toggle('plx-theme-light', isLight);
    btnTheme.textContent = isLight ? '🌙' : '☀';
  });

  titleRow.appendChild(titleSpan);
  titleRow.appendChild(btnTheme);

  /* ── Ligne contrôles ── */
  var controls = document.createElement('div');
  controls.id = 'plx-tts-controls';

  var btnPrev  = mkBtn('{$svgPrev}',  {$lBtnPrev},     'plx-tts-btn-prev');
  var btnPlay  = mkBtn('{$svgPlay}',  {$lBtnPlay},     'plx-tts-btn-play');
  var btnPause = mkBtn('{$svgPause}', {$lBtnPause},    'plx-tts-btn-pause');
  var btnStop  = mkBtn('{$svgStop}',  {$lBtnStop},     'plx-tts-btn-stop');
  var btnNext  = mkBtn('{$svgNext}',  {$lBtnNext},     'plx-tts-btn-next');
  var btnGear  = mkBtn('{$svgGear}',  {$lBtnSettings}, 'plx-tts-btn-gear');
  var btnDys   = mkBtn('Aa',          {$lBtnDyslexia}, 'plx-tts-btn-dys');

  btnPrev.disabled  = true;
  btnPause.disabled = true;
  btnStop.disabled  = true;
  btnNext.disabled  = true;
  if (S.dyslexia) btnDys.classList.add('plx-active');
  if (!hasFonts) btnDys.style.display = 'none';

  var track = document.createElement('div'); track.className = 'plx-tts-track';
  var fill  = document.createElement('div'); fill.className  = 'plx-tts-fill';
  track.appendChild(fill);

  var counter = document.createElement('span'); counter.className = 'plx-tts-counter';

  var sep1 = document.createElement('span'); sep1.className = 'plx-tts-sep';
  var sep2 = document.createElement('span'); sep2.className = 'plx-tts-sep';

  controls.appendChild(btnPrev);
  controls.appendChild(btnPlay);
  controls.appendChild(btnPause);
  controls.appendChild(btnStop);
  controls.appendChild(btnNext);
  controls.appendChild(sep1);
  controls.appendChild(track);
  controls.appendChild(counter);
  controls.appendChild(sep2);
  controls.appendChild(btnGear);
  controls.appendChild(btnDys);

  /* panelSettings et panelDys sont appendés à bar après leur construction ci-dessous */

  /* Appliquer le thème clair sur le body pour les panneaux */

  /* ══════════════════════
     Panneau réglages
     ══════════════════════ */
  var panelSettings = document.createElement('div');
  panelSettings.className = 'plx-tts-panel';
  panelSettings.id = 'plx-tts-panel-settings';
  panelSettings.setAttribute('role', 'region');
  panelSettings.setAttribute('aria-label', {$lSettingsRegion});

  var titleS = document.createElement('div');
  titleS.className = 'plx-tts-panel-title';
  titleS.textContent = {$lSettingsTitle};
  panelSettings.appendChild(titleS);

  /* Voix */
  var rVoice = mkRow({$lVoice});
  var selVoice = document.createElement('select');
  selVoice.className = 'plx-voice-sel';
  fillVoiceSelect(selVoice);
  selVoice.addEventListener('change', function(){ S.voice = selVoice.value; saveSettings(S); });
  rVoice.appendChild(selVoice);
  panelSettings.appendChild(rVoice);

  /* Vitesse */
  var rRate = mkRow({$lRate});
  var iRate = mkRange(0.5, 2, 0.1, S.rate);
  var vRate = mkVal(S.rate.toFixed(1)+'x');
  iRate.addEventListener('input', function(){
    S.rate = parseFloat(iRate.value);
    vRate.textContent = S.rate.toFixed(1)+'x';
    saveSettings(S);
  });
  rRate.appendChild(iRate); rRate.appendChild(vRate);
  panelSettings.appendChild(rRate);

  /* Pitch */
  var rPitch = mkRow({$lPitch});
  var iPitch = mkRange(0.5, 2, 0.1, S.pitch);
  var vPitch = mkVal(S.pitch.toFixed(1));
  iPitch.addEventListener('input', function(){
    S.pitch = parseFloat(iPitch.value);
    vPitch.textContent = S.pitch.toFixed(1);
    saveSettings(S);
  });
  rPitch.appendChild(iPitch); rPitch.appendChild(vPitch);
  panelSettings.appendChild(rPitch);

  /* ══════════════════════
     Panneau dyslexie
     ══════════════════════ */
  var panelDys = document.createElement('div');
  panelDys.className = 'plx-tts-panel';
  panelDys.id = 'plx-tts-panel-dys';
  panelDys.setAttribute('role', 'region');
  panelDys.setAttribute('aria-label', {$lDyslexiaRegion});

  var titleD = document.createElement('div');
  titleD.className = 'plx-tts-panel-title';
  titleD.textContent = {$lDyslexiaTitle};
  panelDys.appendChild(titleD);

  var tDys = mkToggle('plx-dys-toggle', {$lDyslexiaLabel}, S.dyslexia);
  tDys.input.addEventListener('change', function(){
    S.dyslexia = tDys.input.checked;
    saveSettings(S);
    applyDyslexia(S.dyslexia);
  });
  panelDys.appendChild(tDys.row);

  var infoD = document.createElement('p');
  infoD.style.cssText = 'margin:4px 0 0;font-size:.68rem;opacity:.4;line-height:1.5;';
  infoD.textContent = 'Nécessite OpenDyslexic-*.woff2 dans plugins/plxTTS/fonts/';
  panelDys.appendChild(infoD);
  if (!hasFonts) panelDys.style.display = 'none';

  /* ── Assemblage final de la barre (ordre DOM = ordre visuel bas→haut) ── */
  bar.appendChild(titleRow);   /* titre au-dessus des contrôles */
  bar.appendChild(controls);   /* ligne contrôles */
  bar.appendChild(panelSettings); /* panneaux absolus, hors flux */
  bar.appendChild(panelDys);
  document.body.appendChild(bar);

  /* ── Toggle panneaux ── */
  function togglePanel(panel, btn) {
    var isOpen = panel.classList.contains('plx-open');
    /* Fermer les deux */
    panelSettings.classList.remove('plx-open');
    panelDys.classList.remove('plx-open');
    btnGear.classList.remove('plx-active');
    btnDys.classList.remove('plx-active');
    /* Rouvrir si c'était fermé + réappliquer dyslexia active state */
    if (!isOpen) {
      panel.classList.add('plx-open');
      btn.classList.add('plx-active');
    }
    /* Conserver le highlight dyslexie si actif */
    if (S.dyslexia) btnDys.classList.add('plx-active');
  }

  btnGear.addEventListener('click', function(e){
    e.stopPropagation();
    togglePanel(panelSettings, btnGear);
  });
  btnDys.addEventListener('click', function(e){
    e.stopPropagation();
    togglePanel(panelDys, btnDys);
  });

  /* Empêcher les clics dans les panneaux de remonter jusqu'au document */
  panelSettings.addEventListener('click', function(e){ e.stopPropagation(); });
  panelDys.addEventListener('click',      function(e){ e.stopPropagation(); });

  document.addEventListener('click', function(){
    panelSettings.classList.remove('plx-open');
    panelDys.classList.remove('plx-open');
    btnGear.classList.remove('plx-active');
    if (S.dyslexia) btnDys.classList.add('plx-active');
    else btnDys.classList.remove('plx-active');
  });

  /* ══════════════════════
     Collecte des articles
     ══════════════════════ */
  var articles = [];
  var activeArticle = null;

  document.querySelectorAll('article').forEach(function(article, idx){
    var blocks = Array.from(article.querySelectorAll(SELECTORS)).filter(function(el){
      return !el.closest('pre, code') && el.textContent.trim().length > 0;
    });
    if (!blocks.length) return;
    article.dataset.plxIdx = idx;
    articles.push({ el: article, blocks: blocks, visible: false });

    /* Skip link sr-only — visible uniquement aux lecteurs d'écran */
    var skip = document.createElement('a');
    skip.className = 'plx-sr-only';
    skip.href = '#plx-tts-bar';
    skip.textContent = {$lSkipLink};
    skip.addEventListener('click', function(e){
      e.preventDefault();
      /* Cibler cet article et lancer la lecture */
      var art = articles.find(function(a){ return a.el === article; });
      if (art) {
        if (speaking) stop();
        activeArticle = art;
        updateTitle(art);
        bar.classList.add('plx-visible');
        play();
      }
      /* Déplacer le focus vers la barre */
      bar.setAttribute('tabindex', '-1');
      bar.focus();
    });
    article.insertBefore(skip, article.firstElementChild);
  });

  if (!articles.length) return;

  /* Titre de l'article actif (35 car. max) */
  function updateTitle(art) {
    if (!art) { titleSpan.textContent = ''; return; }
    var h = art.el.querySelector('h1, h2');
    var txt = h ? h.textContent.trim() : '';
    if (txt.length > 35) txt = txt.slice(0, 34) + '…';
    titleSpan.textContent = isIntro ? txt + ' (' + lExcerpt + ')' : txt;
  }

  /* ══════════════════════
     IntersectionObserver
     Active le lecteur sur l'article le plus visible
     ══════════════════════ */
  var io = new IntersectionObserver(function(entries){
    entries.forEach(function(entry){
      var idx = parseInt(entry.target.dataset.plxIdx);
      var art = articles.find(function(a){ return a.el === entry.target; });
      if (art) art.visible = entry.isIntersecting;
    });

    /* Article le plus centré dans le viewport */
    var best = null, bestScore = -1;
    articles.forEach(function(art){
      if (!art.visible) return;
      var r = art.el.getBoundingClientRect();
      var center = window.innerHeight / 2;
      var artCenter = r.top + r.height / 2;
      var score = 1 - Math.abs(artCenter - center) / window.innerHeight;
      if (score > bestScore) { bestScore = score; best = art; }
    });

    if (best && best !== activeArticle) {
      /* Stopper lecture si on change d'article */
      if (speaking) stop();
      activeArticle = best;
      updateTitle(best);
    }

    bar.classList.toggle('plx-visible', !!best);
    if (!best) updateTitle(null);
  }, { threshold: [0, 0.1, 0.3, 0.5] });

  articles.forEach(function(art){ io.observe(art.el); });

  /* ══════════════════════
     État lecture
     ══════════════════════ */
  var current  = -1;
  var speaking = false;
  var paused   = false;

  function blocks() {
    return activeArticle ? activeArticle.blocks : [];
  }

  function highlight(idx) {
    /* Effacer tous les surlignages */
    articles.forEach(function(art){
      art.blocks.forEach(function(b){ b.classList.remove('plx-tts-active'); });
    });
    if (idx >= 0 && idx < blocks().length) {
      blocks()[idx].classList.add('plx-tts-active');
      blocks()[idx].scrollIntoView({ behavior:'smooth', block:'center' });
    }
  }

  function updateUI() {
    var total = blocks().length;
    var hasBlocks = total > 0;
    btnPrev.disabled  = !hasBlocks || current <= 0;
    btnPlay.disabled  = speaking && !paused;
    btnPause.disabled = !speaking || paused;
    btnStop.disabled  = !speaking && current < 0;
    btnNext.disabled  = !hasBlocks || current >= total - 1;
    fill.style.width  = total ? ((Math.max(0,current)/total)*100)+'%' : '0%';
    counter.textContent = (speaking || current >= 0)
      ? (Math.max(0,current)+(speaking?1:0))+' / '+total : '';
  }

  function speakBlock(idx) {
    if (!activeArticle || idx >= blocks().length) { stop(); return; }
    current = idx;
    highlight(idx);
    updateUI();

    var utt   = new SpeechSynthesisUtterance(blocks()[idx].textContent.trim());
    utt.lang  = lang;
    utt.rate  = S.rate;
    utt.pitch = S.pitch;
    if (S.voice) {
      var v = voices.find(function(v){ return v.name === S.voice; });
      if (v) utt.voice = v;
    }
    utt.onend   = function(){ if (speaking && !paused) speakBlock(idx+1); };
    utt.onerror = function(e){ if (e.error !== 'interrupted') console.warn('TTS:',e.error); };
    window.speechSynthesis.speak(utt);
  }

  function play() {
    /* Fix Chrome : resume() ignoré après pause longue — on repart du bloc courant */
    window.speechSynthesis.cancel();
    speaking = true; paused = false;
    speakBlock(current >= 0 ? current : 0);
  }

  function pause() {
    if (!speaking) return;
    window.speechSynthesis.cancel(); /* on garde current pour reprendre */
    paused = true; speaking = false;
    highlight(-1); /* effacer le surlignage pendant la pause */
    updateUI();
  }

  function stop() {
    window.speechSynthesis.cancel();
    speaking = false; paused = false; current = -1;
    articles.forEach(function(art){
      art.blocks.forEach(function(b){ b.classList.remove('plx-tts-active'); });
    });
    fill.style.width = '0%'; counter.textContent = '';
    updateUI();
  }

  function prevBlock() {
    var target = current > 0 ? current - 1 : 0;
    window.speechSynthesis.cancel();
    speaking = true; paused = false;
    speakBlock(target);
  }

  function nextBlock() {
    var total = blocks().length;
    if (current >= total - 1) return;
    var target = current >= 0 ? current + 1 : 0;
    window.speechSynthesis.cancel();
    speaking = true; paused = false;
    speakBlock(target);
  }

  btnPlay.addEventListener('click',  play);
  btnPause.addEventListener('click', pause);
  btnStop.addEventListener('click',  stop);
  btnPrev.addEventListener('click',  prevBlock);
  btnNext.addEventListener('click',  nextBlock);
  window.addEventListener('pagehide', function(){ window.speechSynthesis.cancel(); });

  /* ── Raccourcis clavier ── */
  document.addEventListener('keydown', function(e) {
    /* Ignorer si focus dans un champ texte */
    var tag = document.activeElement ? document.activeElement.tagName : '';
    if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return;
    /* Ignorer si la barre n'est pas visible */
    if (!bar.classList.contains('plx-visible')) return;

    switch(e.key) {
      case ' ':
        e.preventDefault();
        if (speaking && !paused) pause();
        else play();
        break;
      case 'Escape':
        e.preventDefault();
        stop();
        break;
      case 'ArrowLeft':
        e.preventDefault();
        prevBlock();
        break;
      case 'ArrowRight':
        e.preventDefault();
        nextBlock();
        break;
    }
  });

  updateUI();

  /* ══════════════════════
     Helpers DOM
     ══════════════════════ */
  function mkBtn(content, label, id) {
    var b = document.createElement('button');
    b.className = 'plx-tts-btn';
    if (id) b.id = id;
    /* SVG ou texte */
    if (content.charAt(0) === '<') b.innerHTML = content;
    else b.textContent = content;
    b.setAttribute('aria-label', label);
    b.setAttribute('type', 'button');
    return b;
  }
  function mkRow(labelText) {
    var row = document.createElement('div'); row.className = 'plx-tts-row';
    var lbl = document.createElement('label'); lbl.textContent = labelText;
    row.appendChild(lbl); return row;
  }
  function mkRange(min, max, step, val) {
    var r = document.createElement('input');
    r.type='range'; r.min=min; r.max=max; r.step=step; r.value=val; return r;
  }
  function mkVal(text) {
    var s = document.createElement('span'); s.className='plx-tts-val'; s.textContent=text; return s;
  }
  function mkToggle(id, labelText, checked) {
    var row = document.createElement('div'); row.className = 'plx-tts-toggle';
    var sw  = document.createElement('label'); sw.className = 'plx-tts-switch';
    var inp = document.createElement('input');
    inp.type='checkbox'; inp.id=id; inp.checked=!!checked;
    var trk = document.createElement('span'); trk.className='plx-tts-switch-track';
    sw.appendChild(inp); sw.appendChild(trk);
    var lbl = document.createElement('label'); lbl.htmlFor=id; lbl.textContent=labelText;
    row.appendChild(sw); row.appendChild(lbl);
    return { row:row, input:inp };
  }

}());
</script>
JS;
    }
}
?>
