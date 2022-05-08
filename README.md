BadWordFilter
=============

A bad word filter for dependency free PHP. Pass in a string or multidimensional array to check for the existence of a predefined list of bad words.
Use the list that ships with the application or define your own custom blacklist. BadWordFilter only matches whole words (excluding symbols)
and not partial words. This will match:

```php
$myString = "Don't be a #FOOBAR!";
$clean = BadWordFilter::clean($myString);
var_dump($clean);
// output: "Don't be a #F****R!"
```

but this will not:

```php
$myString = "I am an ASSociative professor";
$clean = BadWordFilter::clean($myString);
var_dump($clean);
// output: "I am an ASSociative professor"
```



<h3>QuickStart Guide</h3>

1) add the following to your composer.json file:

```
"orionstar/bad-word-filter": "3.*"
```

2) Run composer install

```bash
composer install
```

3) Add BadWordFilter to your use list

```php
use orionstar\BadWordFilter\BadWordFilter;
```

4) start cleaning your inputs~

```php
$cleanString = BadWordFilter::clean("my cheesy string");
var_dump($cleanString);
// output: "my c****y string"
```

<h5>INPORTANT NOTE<h5>
<strong>BadWordFilter does not and never will prevent XSS or SQL Injection. Take the proper steps in your code to sanitize all user input before
storing to a database or displaying to the client.</strong>


<h3>Settings options</h3>

BadWordFilter takes 3 options:

```php
$options = [
    'source'        => 'file',
    'source_file'   => __DIR__ . '/bad_words.php',
    'also_check'    => [],
];
```

<h6>Source Types</h6>

<strong>File</strong>

If you specify a source type of "file" you must also specify a source_file or use the default source file included with this package.
The Source File must return an array of words to check for.

<strong>Array</strong>

If you specify a source type of "array" you must also specify a "bad_words_array" key that contains a list of words to check for.
<h6>Also Check</h6>

In addition to the default list specified in the config file or array you can also pass in an "also_check" key that contains an array of words
to flag.

<h3>Overriding Defaults</h3>

You can override the default settings in the constructor if using the class as an instance, or as an optional parameter in the static method call

```php
$myOptions = ['also_check' => ['foobar']];
$filter = new \orionstar\BadWordFilter\BadWordFilter($myOptions);

$cleanString = $filter->clean('Why did you FooBar my application?');
var_dump($cleanString);
// output: "Why did you F****r my application?"
```


<h3>How to handle bad words</h3>

By default bad words will be replaced with the first letter followed by the requisite number of asterisks and then the last letter. Ie:
"Cheese" would become "C****e"

This can be changed to be replaced with a set string by passing the new string as an argument to the "clean" method

```php
$myOptions = ['also_check' => ['cheesy']];
$cleanString = BadWordFilter::clean("my cheesy string", '#!%^", $myOptions);
var_dump($cleanString);
// output: "my #!%^ string"
```

or

```php
$myOptions = ['also_check' => ['cheesy']];
$filter = new \orionstar\BadWordFilter\BadWordFilter($myOptions);
$cleanString = $filter->clean("my cheesy string", "#!$%");
var_dump($cleanString);
// output: "my #!$% string"
```

In case you want to keep bad word and surround it by anything (ex. html tag):

```php
$myOptions = ['also_check' => ['cheesy']];
$filter = new \orionstar\BadWordFilter\BadWordFilter($myOptions);
$cleanString = $filter->clean("my cheesy string", '<span style="color: red;">$0</span>');
var_dump($cleanString);
// output: "my <span style="color: red;">cheesy</span> string"
```

<h3>Full method list</h3>

<h6>isDirty</h6>
<strong>Check if a string or an array contains a bad word</strong>

Params:
  $input - required - array|string

Return:
  Boolean


Usage:

```php
$filter = new \orionstar\BadWordFilter\BadWordFilter();

if ($filter->isDirty(['this is a dirty string'])
{
    /// do something
}
```


<h6>clean</h6>
<strong>
    Clean bad words from a string or an array. By default bad words are replaced with asterisks with the exception of the first and last letter.
    Optionally you can specify a string to replace the words with
</strong>

Params:
    $input - required - array|string
    $replaceWith - optional - string

Return:
    Cleaned array or string

Usage:
```php
$filter = new \orionstar\BadWordFilter\BadWordFilter();
$string = "this really bad string";
$cleanString = $filter->clean($string);
```


<h6>STATIC clean</h6>
<strong>
    Static wrapper around the "clean" method.
</strong>

Params:
    $input - required - array|string
    $replaceWith - optional - string
    $options - optional - array

Return:
    Cleaned array or string

Usage:
```php
$string = "this really bad string";
$cleanString = BadWordFilter::clean($string);
```


<h6>getDirtyWordsFromString</h6>
<strong>Return the matched dirty words</strong>

Params:
    $input - required - string

Return:
    Boolean


Usage:

```php
$filter = new \orionstar\BadWordFilter\BadWordFilter();
if ($badWords = $filter->getDirtyWordsFromString("this really bad string")) {
    echo "You said these bad words: " . implode("<br />", $badWords);
}
```


<h6>getDirtyKeysFromArray</h6>
<strong>After checking an array using the isDirty method you can access the bad keys by using this method</strong>

Params : none

Return:
    String - dot notation of array keys

Usage:

```php
$arrayToCheck = [
    'first' => [
        'bad' => [
            'a' => 'This is a bad string!',
            'b' => 'This is a good string!',
        ],
    ],
    'second' => 'bad bad bad string!',
];

$filter = new \orionstar\BadWordFilter\BadWordFilter();

if($badKeys = $filter->getDirtyKeysFromArray($arrayToCheck))
{
    var_dump($badKeys);
    /* output:

        array(
            0 => 'first.bad.a',
            1 => 'second'
        );
    */
}
```

