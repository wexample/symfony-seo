# X-Robots-Tag noindex sur les réponses marquées #[NoIndex]

Opened: 2026-09-24
Updated: 2026-09-24
Author: agent:main

> Suite de `0629d6128ed7` (le `/robots.txt` composé, livré en `af09cbb`).

## Le problème

`robots.txt` dit « ne crawle pas », jamais « n'indexe pas », et seuls les crawlers polis le lisent. Une URL interdite peut rester dans l'index si elle est liée ailleurs. Les pages de tunnel (et toute page éphémère ou machine) veulent en plus ne pas être indexées.

## Ce qu'on veut

- Un attribut `#[NoIndex]` (dans `src/Attribute/`) posable sur une classe de contrôleur ou une action.
- Un listener `kernel.response` dans ce bundle qui pose `X-Robots-Tag: noindex, nofollow` sur la réponse quand le contrôleur résolu porte l'attribut.
- `symfony-tunnels` le pose sur `AbstractTunnelController`, héritage compris — vérifier que l'attribut est lu sur les classes parentes.

## Tests

- Un contrôleur de fixture avec et sans l'attribut : l'en-tête n'apparaît que sur le premier.
- Attribut posé sur une classe parente abstraite.

## Pas encore commencé
