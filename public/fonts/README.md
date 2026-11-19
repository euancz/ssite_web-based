# Website typography

Only Poppins is loaded, from local compressed WOFF files, with no external requests:

- Poppins: body text, navigation, forms, and buttons; weights 400 to 800.
- Poppins also supplies all headings, content titles, and wordmarks.

The font roles and spacing are defined in public/css/typography.css and loaded
last by the shared Blade typography partial. Common weights are preloaded early
and cached for one year by Apache. Original TTF files and unused font families
remain as source assets only; the website does not load them.

Source: https://github.com/google/fonts in ofl/poppins, ofl/lora, and ofl/cinzel.
Each family's SIL Open Font License is included in this directory.
