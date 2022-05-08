<?php declare(strict_types = 1);

use orionstar\BadWordFilter\BadWordFilter;
use PHPUnit\Framework\TestCase;

class BadWordFilterTest extends TestCase {

    /**
     * Test that you can clean an html wrapped string and return html that
     * has not been replaced with '*' as per bug report
     */
    public function testHtmlWrapper(): void
    {
        $filter = new BadWordFilter(['also_check' => ['bad word']]);

        static::assertEquals('<h3>b******d</h3>some text', $filter->clean('<h3>bad word</h3>some text'));
    }

    /**
     * Default cleaning works
     */
    public function testBadWordsAreCleaned(): void
    {
        $filter = new BadWordFilter();

        static::assertEquals('s**t', $filter->clean('shit'));
        static::assertEquals('f**k', $filter->clean('fuck'));
        static::assertEquals('d******d', $filter->clean('dickhead'));
        static::assertEquals('a**', $filter->clean('ass'));
    }

    /**
     * Should prefer the supplied replacement string instead of asterisks
     */
    public function testCustomReplace(): void
    {
        $filter = new BadWordFilter(['also_check' => ['replace me']]);
        $replaceWith = '#!<>*&';

        static::assertEquals($replaceWith, $filter->clean('replace me', $replaceWith));
    }

    /**
     * Words that have special characters touching them should be treated
     * the same as words with spaces surrounding them
     */
    public function testSpecialCharactersAreIgnored(): void
    {
        $filter = new BadWordFilter(['also_check' => ['replace me']]);

        static::assertEquals('#r********e', $filter->clean('#replace me'));
        static::assertEquals('^r********e', $filter->clean('^replace me'));
        static::assertEquals('%r********e', $filter->clean('%replace me'));
        static::assertEquals('$r********e', $filter->clean('$replace me'));
        static::assertEquals('@r********e', $filter->clean('@replace me'));
        static::assertEquals('!r********e', $filter->clean('!replace me'));
        static::assertEquals('r********e!', $filter->clean('replace me!'));
        static::assertEquals('(r********e)', $filter->clean('(replace me)'));
        static::assertEquals('<r********e>', $filter->clean('<replace me>'));
    }

    /**
     * Words that contain bad words should not match
     */
    public function testPartialMatchesDontGetCleaned(): void
    {
        $filter = new BadWordFilter();
        $myString = 'I am an ASSociative professor';

        static::assertEquals($myString, $filter->clean($myString));
    }

    /**
     * Should be able to determine if a string has filth in it
     */
    public function testIsDirtyFindsDirtyString(): void
    {
        $filter = new BadWordFilter();

        static::assertFalse($filter->isDirty('my very clean string'));
        static::assertTrue($filter->isDirty('my very fucking dirty string'));
    }

    /**
     * able to get a list of dirty words that are in a string
     */
    public function testCanGetListOfDirtyWordsFromString(): void
    {
        $filter = new BadWordFilter();

        static::assertEquals([
            'fucking',
        ], $filter->getDirtyWordsFromString('my very fucking dirty string'));

        static::assertEquals([
            'fucking',
            'shitty'
        ], $filter->getDirtyWordsFromString('my very fucking shitty dirty string'));
    }

    /**
     * Can parse an array and get list of dirty strings and their array key
     */
    public function testCanGetListOfDirtyWordsFromArray(): void
    {
        $filter = new BadWordFilter();

        static::assertEquals([
                '1',
                '2',
                'filth',
        ], $filter->getDirtyKeysFromArray(['this is a clean string', 'this shit is dirty', 'fuck yo couch', 'actually that is a nice couch!', 'filth' => 'another shitty string']));
    }

}
