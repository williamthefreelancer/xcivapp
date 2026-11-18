"""Refresh self-hosted latin font faces from Fontsource (jsDelivr)."""
from __future__ import annotations

import pathlib
import urllib.request

ROOT = pathlib.Path(__file__).resolve().parents[1] / "assets" / "fonts"
DOWNLOADS = [
	("LeagueGothic-Regular.woff2", "https://cdn.jsdelivr.net/fontsource/fonts/league-gothic@5.2.5/latin-400-normal.woff2"),
	("LeagueGothic-Regular-ext.woff2", "https://cdn.jsdelivr.net/fontsource/fonts/league-gothic@5.2.5/latin-ext-400-normal.woff2"),
	("OpenSans-Regular.woff2", "https://cdn.jsdelivr.net/fontsource/fonts/open-sans@5.2.5/latin-400-normal.woff2"),
	("OpenSans-Regular-ext.woff2", "https://cdn.jsdelivr.net/fontsource/fonts/open-sans@5.2.5/latin-ext-400-normal.woff2"),
	("OpenSans-SemiBold.woff2", "https://cdn.jsdelivr.net/fontsource/fonts/open-sans@5.2.5/latin-600-normal.woff2"),
	("OpenSans-SemiBold-ext.woff2", "https://cdn.jsdelivr.net/fontsource/fonts/open-sans@5.2.5/latin-ext-600-normal.woff2"),
]


def main() -> None:
	ROOT.mkdir(parents=True, exist_ok=True)
	for name, url in DOWNLOADS:
		dest = ROOT / name
		urllib.request.urlretrieve(url, dest)
		print(f"OK {name} ({dest.stat().st_size} bytes)")
	print("Update assets/fonts/snf-fonts.css if face names change.")


if __name__ == "__main__":
	main()
