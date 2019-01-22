
# MrCli - GitLab pending merge requests overview [![Build Status](https://img.shields.io/travis/danielpieper/mrcli.svg?branch=master&style=flat-square)](https://travis-ci.org/danielpieper/mrcli?branch=master)

[![Scrutinizer Coverage](https://img.shields.io/scrutinizer/coverage/g/danielpieper/mrcli.svg?branch=master&style=flat-square)](https://scrutinizer-ci.com/g/danielpieper/mrcli/?branch=master)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/danielpieper/mrcli.svg?branch=master&style=flat-square)](https://scrutinizer-ci.com/g/danielpieper/mrcli/?branch=master)
[![License](https://img.shields.io/badge/license-MIT-red.svg?style=flat-square)](LICENSE)


MrCli checks your gitlab instance for pending merge requests.

Get an overview about the total number of pending mr's by approvers and projects,
list mr's for your own, your colleagues or multiple projects.


## Installation

Install the latest version with

```bash
$ composer global require danielpieper/mrcli
```

## Configuration

`mrcli` is configured using environment variables. 
The `GITLAB_TOKEN` is required. Create a token with the `api` scope: https://gitlab.com/profile/personal_access_tokens
```dotenv
GITLAB_URL=https://gitlab.com # optional, set for on-premise installations
GITLAB_TOKEN=<gitlab token> # create gitlab token with api access: https://gitlab.com/profile/personal_access_tokens
SLACK_WEBHOOK_URL=<slack webhook url> # optional
SLACK_CHANNEL=<slack channel name, for example #merge_requests> # optional
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

