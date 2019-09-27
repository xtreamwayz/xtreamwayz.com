HUGO := hugo
ASSETS_DIR := assets

build-js:
	mkdir -p $(ASSETS_DIR)/js/
	mkdir -p $(ASSETS_DIR)/svg/
	cp node_modules/@fortawesome/fontawesome-free/svgs/brands/github.svg $(ASSETS_DIR)/svg/
	cp node_modules/@fortawesome/fontawesome-free/svgs/brands/instagram.svg $(ASSETS_DIR)/svg/
	cp node_modules/@fortawesome/fontawesome-free/svgs/brands/twitter.svg $(ASSETS_DIR)/svg/
	cp node_modules/@fortawesome/fontawesome-free/svgs/brands/whatsapp.svg $(ASSETS_DIR)/svg/
	cp node_modules/@fortawesome/fontawesome-free/svgs/solid/envelope.svg $(ASSETS_DIR)/svg/
	cp node_modules/@fortawesome/fontawesome-free/svgs/solid/mobile-alt.svg $(ASSETS_DIR)/svg/

build: build-js
	$(HUGO)

serve: build-js
	$(HUGO) server --buildDrafts --buildFuture --bind 172.29.209.162 --baseURL http://172.29.209.162/

generate-githubpages:
	rm -fr docs && HUGO_ENV=production $(HUGO) --baseURL https://marcanuy.github.io/hugo-pipes-bootstrap/ && mv public docs && touch docs/.nojekyll
