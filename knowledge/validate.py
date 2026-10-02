"""Valida brands.json: referencias cruzadas entre marcas, categorías y dominios."""
import json
import sys
from pathlib import Path

data = json.loads((Path(__file__).parent / "brands.json").read_text(encoding="utf-8"))
brand_ids = {b["id"] for b in data["brands"]}
cat_ids = {c["id"] for c in data["categories"]}
dom_ids = {d["id"] for d in data["domains"]}
errors = []

if len(brand_ids) != len(data["brands"]):
    errors.append("ids de marca duplicados")
for b in data["brands"]:
    if b["category"] not in cat_ids:
        errors.append(f"{b['id']}: categoría inexistente {b['category']}")
    if b["tier"] not in ("primer_orden", "cross_selling"):
        errors.append(f"{b['id']}: tier inválido {b['tier']}")
    for k in ("name", "summary", "problems"):
        if not b.get(k):
            errors.append(f"{b['id']}: falta {k}")
for c in data["categories"]:
    if c["domain"] not in dom_ids:
        errors.append(f"{c['id']}: dominio inexistente {c['domain']}")
    for ref in c["first_order"] + c["cross_sell"]:
        if ref not in brand_ids:
            errors.append(f"{c['id']}: marca inexistente {ref}")

print(f"{len(data['brands'])} marcas, {len(cat_ids)} categorías, {len(dom_ids)} dominios")
if errors:
    print("\n".join(errors))
    sys.exit(1)
print("OK")
