# Tipni Jinak WordPress Theme

WordPress šablona vytvořená na základě statických HTML souborů pro sázecí web Tipni Jinak.

## Požadavky

- WordPress 5.8+
- PHP 7.4+
- ACF PRO plugin (pro pokročilá vlastní pole)

## Instalace

1. Nahrajte složku šablony do adresáře `/wp-content/themes/`
2. Aktivujte šablonu v administraci WordPress v sekci Vzhled > Šablony
3. Nainstalujte a aktivujte ACF PRO plugin
4. ACF pole jsou připravena v adresáři `/acf-json/` a budou automaticky importována při aktivaci šablony

## Funkce šablony

### Custom Post Types
- **Soutěže** - pro vytváření různých soutěží (hlavní, vedlejší, atd.)
- **Zápasy** - jednotlivé zápasy pro tipování
- **Týmy** - detail týmů s logy

### Taxonomie
- **Liga** - kategorizace zápasů a týmů podle ligy (Česká, Německá, atd.)
- **Typ soutěže** - kategorizace soutěží (Fotbal, Hokej, atd.)

### ACF Pole
- Pole pro Soutěže (body za tip, hlavní soutěž, atd.)
- Pole pro Zápasy (skóre, týmy, datum, atd.)
- Pole pro Týmy (logo, zkratka, atd.)
- Nastavení šablony (partneři, hlavička, patička, atd.)

### Šablony stránek
- Hlavní stránka
- Stránka hlavní soutěže
- Stránka soutěže
- Stránka zápasu
- a další...

### Menu
- **Primary Menu** - hlavní navigace v hlavičce
- **Footer Menu** - menu v patičce
- **Copyright Menu** - odkazy v patičce v sekci copyright

## Struktura šablony

```
template-wp/
  ├── assets/
  │   ├── css/          - Styly
  │   ├── js/           - JavaScript soubory
  │   └── images/       - Obrázky
  ├── inc/              - Pomocné funkce
  ├── template-parts/   - Části šablony
  ├── functions.php     - Hlavní funkce šablony
  ├── header.php        - Hlavička
  ├── footer.php        - Patička
  ├── index.php         - Hlavní šablona
  └── ...               - Další soubory
```

## Použití

### Vytvoření hlavní soutěže
1. V administraci vytvořte novou soutěž v sekci "Soutěže"
2. Vyplňte název, popis a obrázek
3. Zaškrtněte pole "Hlavní soutěž"
4. Přiřaďte Typ soutěže
5. Nastavte body za tip
6. Přiřaďte zápasy

### Vytvoření týmu
1. V administraci vytvořte nový tým v sekci "Týmy"
2. Vyplňte název týmu
3. Nahrajte logo
4. Vyplňte zkratku týmu
5. Přiřaďte ligu

### Vytvoření zápasu
1. V administraci vytvořte nový zápas v sekci "Zápasy"
2. Vyplňte název zápasu
3. Vyberte domácí a hostující tým
4. Nastavte datum a čas zápasu
5. Nastavte skóre (pokud je již známé)
6. Nastavte stav zápasu (plánovaný, probíhající, ukončený, zrušený)
7. Přiřaďte ligu

## Přispívání

1. Vytvořte fork repozitáře
2. Vytvořte novou větev (`git checkout -b feature/nova-funkce`)
3. Proveďte potřebné změny
4. Vytvořte Pull Request

## Licence

Tato šablona je dostupná pod licencí GNU General Public License v2 nebo novější.