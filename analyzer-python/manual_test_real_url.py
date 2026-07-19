# 6
# Script manuel bach ntestiw ga3 les fonctions dyal keyword_density.py
# m3a chi site réel (pas HTML statique).
#
# Usage:
#   docker compose exec analyzer python manual_test_real_url.py https://exemple.com

# ----------------------------------------------------------------------------

import sys

from analyzer.seo_analyzer import fetch_page
from analyzer.html_parser import parse_html
from analyzer.keyword_density import (
    build_seo_text,
    clean_text,
    tokenize,
    get_bigrams,
    calculate_keyword_density,
)


def print_section(title: str):
    print("\n" + "=" * 70)
    print(title)
    print("=" * 70)


def main():
    # -----------------------------
    # Récupérer l'URL (argument ou valeur par défaut)
    # -----------------------------
    url = sys.argv[1] if len(sys.argv) > 1 else "https://example.com"

    print_section(f"1. FETCH PAGE : {url}")

    page = fetch_page(url)

    if not page["success"]:
        print(f"❌ Erreur: {page['error']}")
        return

    print(f"✅ Status code: {page['status_code']}")
    print(f"✅ Response time: {page['response_time_ms']} ms")
    print(f"✅ Content-Type: {page['content_type']}")
    print(f"✅ HTML length: {len(page['html'])} caractères")

    # -----------------------------
    # Parser le HTML
    # -----------------------------
    print_section("2. PARSE HTML")

    soup = parse_html(page["html"])
    print("✅ BeautifulSoup object créé")

    # -----------------------------
    # Étape 1: build_seo_text
    # -----------------------------
    print_section("3. BUILD SEO TEXT (Title + Meta + H1-H6 + Body)")

    raw_text = build_seo_text(soup)
    print(f"Longueur du texte brut: {len(raw_text)} caractères")
    print(f"Aperçu (300 premiers caractères):\n{raw_text[:300]}...")

    # -----------------------------
    # Étape 2: clean_text
    # -----------------------------
    print_section("4. CLEAN TEXT (lowercase, retrait ponctuation/chiffres)")

    cleaned = clean_text(raw_text)
    print(f"Longueur du texte nettoyé: {len(cleaned)} caractères")
    print(f"Aperçu (300 premiers caractères):\n{cleaned[:300]}...")

    # -----------------------------
    # Étape 3: tokenize
    # -----------------------------
    print_section("5. TOKENIZE (filtrage stop words + longueur min)")

    tokens = tokenize(cleaned)
    print(f"Nombre total de tokens: {len(tokens)}")
    print(f"20 premiers tokens: {tokens[:20]}")

    # -----------------------------
    # Étape 4: get_bigrams
    # -----------------------------
    print_section("6. GET BIGRAMS (expressions de 2 mots)")

    bigrams = get_bigrams(tokens)
    print(f"Nombre total de bigrammes: {len(bigrams)}")
    print(f"10 premiers bigrammes: {bigrams[:10]}")

    # -----------------------------
    # Étape 5: calculate_keyword_density (résultat final)
    # -----------------------------
    print_section("7. CALCULATE KEYWORD DENSITY (résultat final Top 10)")

    result = calculate_keyword_density(soup, top_n=10)

    if not result:
        print("⚠️  Aucun mot-clé détecté (page trop courte ou vide après filtrage).")
        return

    print(f"{'Mot-clé':<30}{'Occurrences':<15}{'Densité %':<10}")
    print("-" * 55)

    for item in result:
        print(f"{item['keyword']:<30}{item['occurrences']:<15}{item['density_percent']:<10}")

    # -----------------------------
    # Vérifications de cohérence
    # -----------------------------
    print_section("8. VÉRIFICATIONS DE COHÉRENCE")

    total_density_unigrams = sum(
        item["density_percent"] for item in result
        if " " not in item["keyword"]
    )
    print(f"Somme des densités (unigrammes seulement): {round(total_density_unigrams, 2)}%")
    print("(Normal si < 100%, vu qu'on garde que le Top 10)")

    keywords_list = [item["keyword"] for item in result]
    has_duplicates = len(keywords_list) != len(set(keywords_list))
    print(f"Doublons détectés: {'❌ OUI (bug)' if has_duplicates else '✅ Non'}")

    is_sorted = all(
        result[i]["occurrences"] >= result[i + 1]["occurrences"]
        for i in range(len(result) - 1)
    )
    print(f"Tri décroissant respecté: {'✅ Oui' if is_sorted else '❌ Non (bug)'}")


if __name__ == "__main__":
    main()