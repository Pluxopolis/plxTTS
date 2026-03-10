# plxTTS — Lecteur Text-to-Speech pour PluXml

Plugin open source pour [PluXml](https://www.pluxml.org) ajoutant un lecteur audio Text-to-Speech accessible, sans dépendance externe, basé sur la Web Speech API native des navigateurs.

---

## Fonctionnalités

- Lecture paragraphe par paragraphe avec surlignage du bloc en cours
- Navigation ⏮ ⏭ entre paragraphes
- Raccourcis clavier : `Espace` play/pause, `Échap` stop, `←` `→` blocs précédent/suivant
- Réglages mémorisés (localStorage) : voix, vitesse, tonalité, thème clair/sombre
- Mode dyslexie : police OpenDyslexic + interlignage élargi *(si polices présentes)*
- Barre fixe via `IntersectionObserver` — compatible tous layouts CSS
- Affichage `(Extrait)` sur les pages de liste (home, catégorie, tags, archives)
- Accessibilité WCAG : ARIA complet, `aria-live`, skip links `sr-only` par article
- Multilingue : fr, en, es, de (extensible)

---

## Installation

1. Copier le dossier `plxTTS/` dans `plugins/` de PluXml
2. Activer depuis l'administration
3. *(Optionnel)* Déposer les fichiers woff2 OpenDyslexic dans `plugins/plxTTS/fonts/`

### Polices OpenDyslexic

Télécharger depuis [github.com/antijingoist/opendyslexic](https://github.com/antijingoist/opendyslexic/tree/master/compiled) :

```
OpenDyslexic-Regular.woff2
OpenDyslexic-Bold.woff2
OpenDyslexic-Italic.woff2
```

Le bouton `Aa` apparaît automatiquement si les fichiers sont présents, est masqué sinon.

---

## Structure

```
plxTTS/
├── plxTTS.php       — Plugin principal (CSS + JS générés par PHP)
├── infos.xml        — Métadonnées PluXml
├── icon.png         — Icône back-office (64×64)
├── fonts/
│   └── README.txt   — Instructions polices OpenDyslexic
└── lang/
    ├── fr.php       — Français
    ├── en.php       — Anglais
    ├── es.php       — Espagnol
    └── de.php       — Allemand
```

---

## Architecture technique

### Hooks PluXml utilisés

| Hook | Rôle |
|------|------|
| `plxMotorDemarrageEnd` | Détecte le mode de la page (`article`, `static`, ou liste) |
| `ThemeEndHead` | Injecte le CSS (dont `@font-face` conditionnels) |
| `ThemeEndBody` | Injecte le JavaScript |

### Détection du mode page

Le hook `plxMotorDemarrageEnd` est le bon endroit pour lire `$this->mode` dans PluXml. La variable `$intro` du plugin est mise à jour via la référence au plugin dans `$this->plxPlugins->aPlugins` :

```php
public function plxMotorDemarrageEnd() {
    echo self::BEGIN_CODE;
    ?>
    if($this->mode == 'article' || $this->mode == 'static') {
        $this->plxPlugins->aPlugins["<?= __CLASS__ ?>"]->intro = false;
    }
    <?php
    echo self::END_CODE;
}
```

### Injection de code PHP dans un hook

Pour émettre du PHP exécutable depuis un hook, PluXml utilise la convention :

```php
const BEGIN_CODE = '<?php' . PHP_EOL;
const END_CODE   = PHP_EOL . '?>';

echo self::BEGIN_CODE;
?>
    // code PHP exécuté dans le contexte du moteur PluXml
<?php
echo self::END_CODE;
```

### Conventions fichiers langue

```php
// lang/fr.php
<?php if (!defined('PLX_ROOT')) exit; ?>
<?php
$LANG['MA_CLE'] = 'Ma valeur';
```

- Le tableau est `$LANG` (majuscules) — pas `$lang`
- `$this->loadLang()` est inutile dans le constructeur, PluXml le gère automatiquement
- Utiliser `$this->getLang('MA_CLE')` pour récupérer une valeur
- Pour injection dans du JavaScript : `json_encode($this->getLang('MA_CLE'))` garantit l'échappement correct

### Génération du JS depuis PHP (heredoc)

Les variables PHP sont interpolées directement dans le heredoc JS :

```php
$maVar = json_encode($this->getLang('MA_CLE'));

echo <<<JS
<script>
  var maVar = {$maVar};
</script>
JS;
```

### IntersectionObserver — lecteur fixe multi-articles

```javascript
var io = new IntersectionObserver(function(entries) {
    // Mettre à jour la visibilité de chaque article
    // Sélectionner l'article le plus centré dans le viewport
    // Afficher/masquer la barre fixe
}, { threshold: [0, 0.1, 0.3, 0.5] });

document.querySelectorAll('article').forEach(function(art) {
    io.observe(art);
});
```

La barre est injectée dans `<body>` (`position:fixed`), indépendante de tout layout. Les panneaux de configuration sont enfants directs de la barre (`position:absolute`), toujours ancrés au lecteur.

### Fix Chrome — pause longue

`speechSynthesis.resume()` est silencieusement ignoré par Chrome après ~15 secondes de pause. Solution : ne jamais utiliser `resume()`, toujours relancer via `cancel()` + `speakBlock(current)` :

```javascript
function play() {
    window.speechSynthesis.cancel();
    speaking = true; paused = false;
    speakBlock(current >= 0 ? current : 0);
}
```

### Accessibilité — skip link sr-only

Lien invisible visuellement mais lisible par les lecteurs d'écran, placé en premier enfant de chaque `<article>` :

```css
.plx-sr-only {
    position: absolute;
    width: 1px; height: 1px;
    padding: 0; margin: -1px;
    overflow: hidden;
    clip: rect(0,0,0,0);
    white-space: nowrap;
    border: 0;
}
```

```javascript
var skip = document.createElement('a');
skip.className = 'plx-sr-only';
skip.href = '#plx-tts-bar';
skip.textContent = 'Lire cet article';
skip.addEventListener('click', function(e) {
    e.preventDefault();
    // Cibler cet article, lancer la lecture, déplacer le focus
});
article.insertBefore(skip, article.firstElementChild);
```

### Détection conditionnelle des polices

```php
$fontsDir = PLX_ROOT . 'plugins/plxTTS/fonts/';
$hasFonts = file_exists($fontsDir . 'OpenDyslexic-Regular.woff2');

// Dans ThemeEndHead : @font-face injectés seulement si polices présentes
// Dans ThemeEndBody : variable JS hasFonts = true/false
// Bouton Aa et panneau dyslexie masqués si !hasFonts
```

---

## Modes PluXml

| `$this->mode` | Description |
|---------------|-------------|
| `home` | Page d'accueil |
| `article` | Article complet |
| `static` | Page statique complète |
| `categorie` | Liste par catégorie |
| `tags` | Liste par tag |
| `archives` | Archives |
| `erreur` | Page d'erreur |

Sur les modes autres que `article` et `static`, PluXml n'affiche généralement que le chapo/introduction des articles.

---

## Ajouter une langue

Créer `lang/xx.php` en copiant `lang/fr.php` et en traduisant les valeurs. PluXml charge automatiquement le bon fichier selon la langue configurée.

---

## Limitations connues

- **Voix** : dépendantes du navigateur et de l'OS — qualité variable, peu de voix sur certains Linux
- **Pas de lecture inter-articles** : la lecture s'arrête au dernier paragraphe
- **Polices OpenDyslexic** : à héberger manuellement (non incluses — licence OFL)

---

## Licence

Plugin distribué librement.  
Police OpenDyslexic : [SIL Open Font License 1.1](https://openfontlicense.org)

---

## Développement

Plugin développé en pair-programming avec [Claude](https://claude.ai) (Anthropic).  
Documentation PluXml de référence :
- [github.com/pluxml/PluXml](https://github.com/pluxml/PluXml)
- [wiki.pluxml.org/docs/develop/plxshow.html](https://wiki.pluxml.org/docs/develop/plxshow.html)
