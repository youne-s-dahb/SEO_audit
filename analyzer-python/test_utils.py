# tester utils

from analyzer.utils import (
    remove_extra_spaces,
    validate_url,
    normalize_url,
    safe_int,
    safe_float
)

print("========== remove_extra_spaces ==========")
print(remove_extra_spaces("   Bonjour      tout     le     monde   "))
# Résultat attendu : Bonjour tout le monde


print("\n========== validate_url ==========")
print(validate_url("https://google.com"))      # True
print(validate_url("http://example.com"))      # True
print(validate_url("ftp://example.com"))       # False
print(validate_url("google.com"))              # False
print(validate_url(""))                        # False


print("\n========== normalize_url ==========")
print(normalize_url("https://google.com/"))    # https://google.com
print(normalize_url("https://google.com"))     # https://google.com


print("\n========== safe_int ==========")
print(safe_int("15"))          # 15
print(safe_int(99))            # 99
print(safe_int("abc"))         # 0
print(safe_int(None))          # 0


print("\n========== safe_float ==========")
print(safe_float("15.5"))      # 15.5
print(safe_float(8))           # 8.0
print(safe_float("abc"))       # 0.0
print(safe_float(None))        # 0.0