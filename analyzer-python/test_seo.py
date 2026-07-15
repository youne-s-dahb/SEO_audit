# tester seo_analyzer

from analyzer.seo_analyzer import fetch_page
from analyzer.seo_analyzer import analyze


# --------------------------------------
# URL de test
# --------------------------------------

url = "https://example.com"

# --------------------------------------
# Lancer le téléchargement
# --------------------------------------

result = fetch_page(url)

# --------------------------------------
# Afficher le résultat
# --------------------------------------

print("========== RESULT ==========\n")

print("Success :", result["success"])

if result["success"]:

    print("Status Code :", result["status_code"])

    print("Response Time :", result["response_time_ms"], "ms")

    print("Content-Type :", result["content_type"])

    print("Final URL :", result["final_url"])

    print("\n========== HTML ==========\n")

    # Afficher uniquement les 500 premiers caractères
    print(result["html"][:500])

else:

    print("Error :", result["error"])


# --------------------------------------
# URL à tester
# --------------------------------------

url = "https://example.com"

# --------------------------------------
# Lancer l'analyse
# --------------------------------------

result = analyze(url)

# --------------------------------------
# Afficher le résultat
# --------------------------------------

print("\n========== RESULT ==========\n")

if result["success"]:

    print("URL :", result["url"])

    print("Status Code :", result["status_code"])

    print("Response Time :", result["response_time_ms"], "ms")

    print("Content-Type :", result["content_type"])

    print("\n========== SEO ==========\n")

    print("Title :", result["title"])

    print("Title Length :", result["title_length"])

    print("Meta Description :", result["meta_description"])

    print("Meta Length :", result["meta_length"])

    print("Canonical :", result["canonical_url"])

    print("Robots :", result["meta_robots"])

    print("Language :", result["language"])

    print("Viewport :", result["viewport"])

    print("Structured Data :", result["structured_data"])

    print("\n========== HEADINGS ==========\n")

    print(result["headings"])

    print("H1 Count :", result["h1_count"])

    print("Unique H1 :", result["h1_is_unique"])

    print("\n========== CONTENT ==========\n")

    print("Word Count :", result["word_count"])

    print("\n========== LINKS ==========\n")

    print("Links Count :", result["links_count"])

    print("Internal Links :", result["internal_links"])

    print("External Links :", result["external_links"])

    print("\nTous les liens :")

    for link in result["links"]:
        print("-", link)

    print("\n========== IMAGES ==========\n")

    print("Images Count :", result["images_count"])

    print("\nImages avec ALT :")

    for image in result["images_with_alt"]:
        print(image)

    print("\nImages sans ALT :")

    for image in result["images_without_alt"]:
        print(image)

else:

    print("Erreur :", result["error"])