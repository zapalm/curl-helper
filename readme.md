# CURL helper
CURL helper is the full documented (for now in Russian) simple PHP-library to do HTTP-requests.
Example:
~~~
$helper = (new CurlHelper())
    ->setPost(false)
    ->setReturn(true)
    ->setHeader(false)
    ->setUserAgent('User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.79 Safari/537.36 Edge/14.14393')
    ->setUrl('https://www.google.com/search?q=hello+world')
;
$result = $helper->execute();
if (false === $result) {
    $errorMessage = $helper->getErrorMessage();
}
~~~

## Installation
Add the dependency directly to your `composer.json` file:
```
"repositories": [
  {
    "type": "vcs",
    "url": "https://github.com/zapalm/curl-helper"
  }
],
"require": {
  "php": ">=5.5",
  "zapalm/curl-helper": "dev-master"
},
```

## How to help the project grow and get updates
Give the **star** to the project. That's all! :)

## Contributing to the code

### Requirements for code contributors

Contributors **must** follow the following rules:

* **Make your Pull Request on the *dev* branch**, NOT the *master* branch.
* Do not update a helper version number.
* Follow [PSR coding standards][1].

### Process in details for code contributors

Contributors wishing to edit the project's files should follow the following process:

1. Create your GitHub account, if you do not have one already.
2. Fork the project to your GitHub account.
3. Clone your fork to your local machine.
4. Create a branch in your local clone of the project for your changes.
5. Change the files in your branch. Be sure to follow [the coding standards][1].
6. Push your changed branch to your fork in your GitHub account.
7. Create a pull request for your changes **on the *dev* branch** of the project.
   If you need help to make a pull request, read the [GitHub help page about creating pull requests][2].
8. Wait for the maintainer to apply your changes.

**Do not hesitate to create a pull request if even it's hard for you to apply the coding standards.**

[1]: https://www.php-fig.org/psr/
[2]: https://help.github.com/articles/about-pull-requests/
