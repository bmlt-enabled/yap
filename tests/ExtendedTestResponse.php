<?php

namespace Tests;

use Illuminate\Testing\Assert;
use Illuminate\Testing\TestResponse;

class ExtendedTestResponse extends TestResponse
{
    public function assertSeeInOrderExact(array $expected): void
    {
        $content = str_replace("\n", "", $this->getContent());

        foreach ($expected as $item) {
            $position = strpos($content, $item);

            // If the current item is not found or it's not at the expected position, fail the test
            if ($position === false || $position !== 0) {
                Assert::fail("Failed asserting that the response exactly contains '$item' that matches '$content'");
            }

            // Remove the matched part of the content to check for subsequent items
            $content = substr($content, $position + strlen($item));
        }
    }
}
