<?php declare(strict_types = 1);

namespace orionstar\BadWordFilter;

use RuntimeException;

class BadWordFilter {

    /**
     * The default configurations for this package
     */
    protected array $defaults = [];

    /**
     * All the configurations for this package. Created by merging user provided configurations
     * with the default configurations
     */
    protected array $config = [];

    /**
     * Manages state of the object, if we are using a custom
     * word list this will be set to true
     */
    protected bool $isUsingCustomDefinedWordList = false;

    /**
     * A list of bad words to check for
     */
    protected array $badWords = [];

    /**
     * The start of the regex we will build to check for bad word matches
     * Match for only the whole substring
     */
    protected string $regexStart = '/';

    /**
     * The end of the regex we ill build to check for bad word matches
     * Match for only the whole substring
     */
    protected string $regexEnd = '/iu';

    /**
     * Create the object and set up the bad words list and
     *
     * @throws \Exception
     */
    public function __construct(array $options = [])
    {
        $this->defaults = include __DIR__ . '/../../config/config.php';

        if ($this->hasAlternateSource($options) || $this->hasAlternateSourceFile($options))
		{
            $this->isUsingCustomDefinedWordList = true;
        }

        $this->config = array_merge($this->defaults, $options);

        $this->getBadWords();
    }

    /**
     * Check if the provided $input contains any bad words
     *
     * @param string|array $input
     */
    public function isDirty($input): bool
    {
        return is_array($input) ? $this->isDirtyArray($input) : $this->isDirtyString($input);
    }

    /**
     * Clean the provided $input and return the cleaned array or string
     *
     * @param string|array $input
     * @param string $replaceWith
     *
     * @return array|string
     */
    public function scrub($input, string $replaceWith = '*')
    {
        return is_array($input) ? $this->cleanArray($input, $replaceWith) : $this->cleanString($input, $replaceWith);
    }

    /**
     * Clean the $input (array or string) and replace bad words with $replaceWith
     *
     * @param string|array $input
     * @param string $replaceWith
     *
     * @return array|string
     */
    public function clean($input, string $replaceWith = '*')
    {
        return $this->scrub($input, $replaceWith);
    }

    /**
     * Get dirty words from the provided $string as an array of bad words
     */
    public function getDirtyWordsFromString(string $string): array
    {
        $badWords = [];
        $wordsToTest = $this->flattenArray($this->badWords);

        foreach ($wordsToTest as $word)
		{
            $word = preg_quote($word, null);

            if(preg_match($this->buildRegex($word), $string, $matchedString))
			{
                $badWords[] = $matchedString[0];
            }
        }

        return $badWords;
    }

    /**
     * Get an array of key/value pairs of dirty keys in the $input array
     */
    public function getDirtyKeysFromArray(array $input = []): array
    {
        return $this->findBadWordsInArray($input);
    }

    /**
     * Create the regular expression for the provided $word
     */
    protected function buildRegex(string $word): string
    {
        return $this->regexStart . '(' . $word . ')' . $this->regexEnd;
    }

    /**
     * Check if the current model is set up to use a custom defined word list
     */
    protected function isUsingCustomDefinedWordList(): bool
    {
        return $this->isUsingCustomDefinedWordList;
    }

    /**
     * Check if the $input array is dirty or not
     */
    protected function isDirtyArray(array $input): bool
    {
        return (bool) $this->findBadWordsInArray($input);
    }

    /**
     * Return an array of bad words that were found in the $input array along with their keys
     */
    protected function findBadWordsInArray(array $input = [], ?string $previousKey = null): array
    {
        $dirtyKeys = [];

        foreach ($input as $key => $value)
		{
            // create the "dot" notation keys
            if ($previousKey !== null)
			{
                $key = $previousKey . '.' . $key;
            }

            if (is_array($value))
			{
                // call recursively to handle multidimensional array,
                $dirtyKeys[] = $this->findBadWordsInArray($value, $key);
            }
			else if(is_string($value) && $this->isDirtyString($value))
			{
				// bad word found, add the current key to the dirtyKeys array
				$dirtyKeys[] = (string) $key;
			}
        }

        return $this->flattenArray($dirtyKeys);
    }

    /**
     * Clean all the bad words from the input $array
     */
    protected function cleanArray(array $array, string $replaceWith): array
    {
        $dirtyKeys = $this->findBadWordsInArray($array);

        foreach ($dirtyKeys as $key)
		{
            $this->cleanArrayKey($key, $array, $replaceWith);
        }

        return $array;
    }

    /**
     * Clean the string stored at $key in the $array
     */
    protected function cleanArrayKey(string $key, &$array, string $replaceWith)
    {
        $keys = explode('.', $key);

        foreach ($keys as $k)
		{
            $array = &$array[$k];
        }

        return $array = $this->cleanString($array, $replaceWith);
    }

    /**
     * Clean the input $string and replace the bad word with the $replaceWith value
     */
    protected function cleanString(string $string, string $replaceWith)
    {
        $words = $this->getDirtyWordsFromString($string);

        if( ! $words)
		{
			return $string;
        }

        foreach($words as $word)
		{
            if($word === '')
			{
                continue;
            }

            if($replaceWith === '*')
			{
                $first_char = $word[0];
                $last_char = $word[strlen($word) - 1];
                $len = strlen($word);

                $newWord = $len > 3 ? $first_char . str_repeat('*', $len - 2) . $last_char : $first_char . '**';
            }
			else
			{
                $newWord = $replaceWith;
            }

            $string = preg_replace("/$word/iu", $newWord, $string);
        }

        return $string;
    }

    /**
     * Check if the $input parameter is a dirty string
     */
    protected function isDirtyString(string $input): bool
    {
        return $this->strContainsBadWords($input);
    }

    /**
     * Check if the input $string contains bad words
     */
    protected function strContainsBadWords(string $string): bool
    {
        return (bool) $this->getDirtyWordsFromString($string);
    }

    /**
     * Set the bad words array to the model if not already set and return it
     *
     * @throws \Exception
     */
    protected function getBadWords(): array
    {
        if ($this->badWords)
		{
			return $this->badWords;
        }

        switch ($this->config['source'])
        {
            case 'file':
                $this->badWords = $this->getBadWordsFromConfigFile();
                break;

            case 'array':
                $this->badWords = $this->getBadWordsFromArray();
                break;

            default:
                throw new RuntimeException('Config source was not a valid type. Valid types are: file, array');
        }

        if ( ! empty($this->config['also_check']))
		{
            if ( ! is_array($this->config['also_check']))
			{
                $this->config['also_check'] = [$this->config['also_check']];
            }

            $this->badWords = array_merge($this->badWords, $this->config['also_check']);
        }

        return $this->badWords;
    }

    /**
     * Get subset of the bad words by an array of $keys
     */
    protected function getBadWordsByKey(array $keys): array
    {
        $bw = [];
        foreach ($keys as $key)
		{
            if ( ! empty($this->badWords[$key]))
			{
                $bw[] = $this->badWords[$key];
            }
        }

        return $bw;
    }

    /**
     * Get the bad words list from a config file
     *
     * @throws \Exception
     */
    protected function getBadWordsFromConfigFile(): array
    {
        if (file_exists($this->config['source_file']))
		{
            return include $this->config['source_file'];
        }

        throw new RuntimeException('Source was config but the config file was not set or contained an invalid path. Tried looking for it at: ' . $this->config['source_file']);
    }

    /**
     * Get the bad words from the array in the config
     */
    protected function getBadWordsFromArray(): array
    {
        if ( ! empty($this->config['bad_words_array']) && is_array($this->config['bad_words_array']))
		{
            return $this->config['bad_words_array'];
        }

		return [];
    }

    /**
     * Flatten the input $array
     */
    protected function flattenArray(array $array): array
    {
       	$objTmp = (object) ['aFlat' => []];

        $callBack = static function(&$v, $k, &$t) {
	        $t->aFlat[] = $v;
        };

        array_walk_recursive($array, $callBack, $objTmp);

        return $objTmp->aFlat;
    }

    protected function hasAlternateSource(array $options): bool
    {
        return ! empty($options['source']) && $options['source'] !== $this->defaults['source'];
    }

    protected function hasAlternateSourceFile(array $options): bool
    {
        return ! empty($options['source_file']) && $options['source_file'] !== $this->defaults['source_file'];
    }

}
