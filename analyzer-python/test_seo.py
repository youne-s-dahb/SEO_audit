# tester seo_analyzer

from analyzer.seo_analyzer import fetch_page

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