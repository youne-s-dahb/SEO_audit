# tester html_parser

from analyzer.html_parser import (
    parse_html,
    get_title,
    get_title_length,
    get_meta_description,
    get_meta_length,
    get_canonical_url,
    get_meta_robots,
    get_lang,
    get_viewport,
    get_headings,
    get_h1_count,
    is_h1_unique,
    count_words,
    get_links,
    count_internal_links,
    count_external_links,
    count_images,
    get_images_with_alt,
    get_images_without_alt,
    has_structured_data
)

html = """
<html lang="fr">

<head>

    <title>Mon site SEO</title>

    <meta name="description" content="Description de test pour le SEO.">

    <meta name="robots" content="index,follow">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="canonical" href="https://monsite.com">

    <script type="application/ld+json">
    {
        "@context":"https://schema.org"
    }
    </script>

</head>

<body>

    <h1>Premier H1</h1>
    <h1>Deuxième H1</h1>

    <h2>H2 Exemple</h2>

    <h4>H4 Exemple</h4>

    <h6>H6 Exemple</h6>

    <img src="logo.png" alt="Logo">

    <img src="banner.jpg" alt="Banner">

    <img src="photo.jpg">

    <img src="test.png">

    <a href="/contact">Contact</a>

    <a href="https://monsite.com/blog">Blog</a>

    <a href="https://google.com">Google</a>

    <p>
        Bonjour tout le monde.
        Ceci est une page de test pour vérifier toutes les fonctions.
    </p>

</body>

</html>
"""

base_url = "https://monsite.com"

soup = parse_html(html)

print("========== TITLE ==========")
print(get_title(soup))
print(get_title_length(soup))

print("\n========== META ==========")
print(get_meta_description(soup))
print(get_meta_length(soup))

print("\n========== CANONICAL ==========")
print(get_canonical_url(soup))

print("\n========== ROBOTS ==========")
print(get_meta_robots(soup))

print("\n========== LANG ==========")
print(get_lang(soup))

print("\n========== VIEWPORT ==========")
print(get_viewport(soup))

print("\n========== HEADINGS ==========")
print(get_headings(soup))

print("\n========== H1 ==========")
print(get_h1_count(soup))
print(is_h1_unique(soup))

print("\n========== WORDS ==========")
print(count_words(soup))

print("\n========== LINKS ==========")
links = get_links(soup)

print(links)

print("Internal :", count_internal_links(links, base_url))
print("External :", count_external_links(links, base_url))

print("\n========== IMAGES ==========")
print("Images count :", count_images(soup))

print("\nImages avec ALT :")
print(get_images_with_alt(soup))

print("\nImages sans ALT :")
print(get_images_without_alt(soup))

print("\n========== STRUCTURED DATA ==========")
print(has_structured_data(soup))