
# MrCli - GitLab merge requests overview [![Build Status](https://img.shields.io/travis/danielpieper/mrcli.svg?branch=master&style=flat-square)](https://travis-ci.org/danielpieper/fints-ofx?branch=master)

[![Scrutinizer Coverage](https://img.shields.io/scrutinizer/coverage/g/danielpieper/mrcli.svg?branch=master&style=flat-square)](https://scrutinizer-ci.com/g/danielpieper/fints-ofx/?branch=master)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/danielpieper/mrcli.svg?branch=master&style=flat-square)](https://scrutinizer-ci.com/g/danielpieper/fints-ofx/?branch=master)
[![License](https://img.shields.io/badge/license-MIT-red.svg?style=flat-square)](LICENSE)


MrCli checks your gitlab instance for pending merge requests.

Get an overview about the total number of pending mr's by approvers and projects,
list mr's for your own, your colleagues or multiple projects.


## Installation

Install the latest version with

```bash
$ composer global require danielpieper/mrcli
```

## Basic Usage

```bash
mrcli overview
mrcli project <project names separated by space>
mrcli approver <approver name, leave empty for your mr's>
```

## About

### Requirements

- MrCli works with PHP 7.1 or above

### Author

Daniel Pieper - <github@daniel-pieper.com>

### License

MrCli is licensed under the MIT License - see the `LICENSE` file for details

