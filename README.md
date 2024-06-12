# BioC annotation

This repository experiments with using the BioC format to hold annotations of scientific articles, and the use of tools such as simple regular expression taggers and CRF to annotate documents.

## BioC format

The BioC annotation format is described in:

> Donald C. Comeau, Rezarta Islamaj Doğan, Paolo Ciccarese, Kevin Bretonnel Cohen, Martin Krallinger, Florian Leitner, Zhiyong Lu, Yifan Peng, Fabio Rinaldi, Manabu Torii, Alfonso Valencia, Karin Verspoor, Thomas C. Wiegers, Cathy H. Wu, W. John Wilbur, BioC: a minimalist approach to interoperability for biomedical text processing, Database, Volume 2013, 2013, bat064, [doi:10.1093/database/bat064](https://doi.org/10.1093/database/bat064)

A JSON version is used by [PubTator](https://www.ncbi.nlm.nih.gov/research/pubtator/).

## Tools

### bioc2html

`bioc2html.php` takes a BioC JSON file and outputs a HTML file with the annotations displayed as coloured boxes on the text (inspired by [PubTator](https://www.ncbi.nlm.nih.gov/research/pubtator/)).

### jats2bioc

`jats2bioc.php` takes a JATS XML file for an article and converts it to BioC JSON. Very crude and incomplete, but key feature is extracting marked-up entities in the XML and outputting them as annotations. These can be visualised using `bioc2html.php`. One use of `jats2bioc.php` is to generate training data from content such as articles from Pensoft where entities such as taxonomic names are often already marked-up.

Note that whereas NER tools expect entire tokens of text to be tagged (for example, including any punctuation such as terminating “,”), typically JATS XML will annotate just the relevant substring. Hence round tripping via NER tools will be a challenge because they will segment the text slightly differently.

### bioc_delete_annotations

`bioc_delete_annotations.php` deletes **ALL** annotations in a BioC file so that we can start again.

### bioc_tagger

`bioc_tagger.php` reads a BioC file and adds annotations for various entities that it finds in the text passages. These entities are found using, for example, simple regular expressions. This tool is intended to be a quick and dirty way of generating training data.

### bioc_name_tagger

`bioc_name_tagger.php` reads a BioC file and annotations scientific names, for example using TaxonFinder.

To run TaxonFinder, download from https://glitch.com/edit/#!/right-frill, then install and run:

```
cd app
npm install
npm start server.js
```

This will give you TaxonFinder running on `localhost:3000`.


### bioc2crf

`bioc2crf.php` takes a BioC JSON file and exports it to a data and template file that can be used by a CRF tool (in progress). Annotations are in [IOB format](https://en.wikipedia.org/wiki/Inside–outside–beginning_(tagging)).


### crf2bioc

`crf2bioc.php` takes results of CRF and generates a BioC JSON file, which can then be visualised using `bioc2html.php`.

## Editing (manual annotation) 

Simple tool (based on `citation-parser`) to take a crude XML version of the BioC data (just paragraphs of text with entities marked up) and make it editable.

## Sources of training data

### Pensoft

JATS XML often has taxon name, localities, and GenBank marked up.

### Plazi

A bit of a mess, often missing things, or getting them wrong. Examples to look at

9CF1B989-D277-4219-8DCD-532EA483E7A9

