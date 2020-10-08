# Nash Link API Documentation

Based on [Slate](https://github.com/slatedocs/slate) and [docuapi](https://github.com/bep/docuapi/).

The docs are automatically built and deployed to Github pages when pushing a git tag with the `docs/` prefix (eg. `docs/2020-08-12`)

Links:

* Live documentation: https://docs-link.nash.io/
* [Github repo](https://github.com/nash-io/nashlink-plugins-and-sdks)

Documentation for content creation:

* Markdown Syntax: https://github.com/slatedocs/slate/wiki/Markdown-Syntax
* Additional syntax that can be used in markdown: https://gohugo.io/content-management/shortcodes/

## Getting Started

* Uses [hugo](https://gohugo.io/getting-started/installing/) (`v0.72.0+`) with the [docuapi](https://github.com/bep/docuapi/) theme
* The documentation content in markdown format is located in [`/content/`](https://github.com/nash-io/nashlink-plugins-and-sdks/tree/master/docs/content)

Start with Docker:

    # Run the development container
    docker run --rm -v $(pwd):/src -p 1313:1313 klakegg/hugo:0.74.3-ext-alpine server

Now you can view the live documentation at http://localhost:1313/. It updates automatically when you edit files.

You can also start a production build:

    docker run --rm -v $(pwd):/src --entrypoint /bin/sh klakegg/hugo:0.74.3-ext-alpine .docker-build-documentation.sh
