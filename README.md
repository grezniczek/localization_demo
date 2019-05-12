# Localization Demo

A REDCap External Module demonstrating EM localization.

## Requirements

- REDCAP 8.1.0 or newer (tested with REDCap 8.11.11 and 9.0.0).

## Installation

- Clone this repo into `<redcap-root>/modules/i18n_demo_v<version-number>`.
- Go to _Control Center > Technical / Developer Tools > External Modules_ and enable Localization Demo.

## Configuration

- Set the text of a message and how it should be logged to the browser's console (info, warning, error).
- Set how verbose the module is about the whole process (mostly nonsense, though).
- Let the module show that it can count up from 1 to a given number.

## Effect

When enabled, it outputs some stuff -depending on the settings- to the browser's console. Very unspectacular, really, if it weren't.