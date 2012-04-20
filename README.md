Directus
=====================

Directus is a free and open source content management framework written in PHP that provides a feature-rich environment for rapid development and management of custom MySQL database solutions. Visit [getdirectus.com](http://getdirectus.com/get) for more info.


Contributing
---------------------

Directus is a community driven project and accepts contributions of code and documentation from the community. These contributions are made in the form of Issues or Pull Requests on the RNGR Directus repository on GitHub. 


Compatibility
---------------------

Directus 5.x is compatible with PHP 5.2+ so all code supplied must stick to this requirement. If features take advantage of newer PHP functions than a fallback for 5.2 must be provided.


PHP Style
---------------------

All code must meet the Style Guide, which is essentially the [OTBS](http://en.wikipedia.org/wiki/Indent_style#Variant:_1TBS) indent style, underscores and non-readable operators. This makes certain that all code is the same format as the existing code and means it will be as readable as possible.


Markup Style
---------------------

Use of semantic selectors is a must.


Documentation
---------------------

If you change anything that requires a change to documentation then you will need to include that in your description.


Branching
---------------------

Directus uses the [Git-Flow branching model](http://nvie.com/posts/a-successful-git-branching-model/) which requires all pull requests to be sent to the "develop" branch. This is where the next planned version will be developed. The "master" branch will always contain the latest stable version and is kept clean so a "hotfix" (e.g: an emergency security patch) can be applied to master to create a new version, without worrying about other features holding it up. For this reason all commits need to be made to "develop" and any sent to "master" will be closed automatically. If you have multiple changes to submit, please place all changes into their own branch on your fork.

One thing at a time: A pull request should only contain one change. That does not mean only one commit, but one change - however many commits it took. The reason for this is that if you change X and Y but send a pull request for both at the same time, we might really want X but disagree with Y, meaning we cannot merge the request. Using the Git-Flow branching model you can create new branches for both of these features and send two requests.