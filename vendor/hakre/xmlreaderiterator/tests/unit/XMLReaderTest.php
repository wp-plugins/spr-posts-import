<?php

/*
 * This file is part of the XMLReaderIterator package.
 *
 * Copyright (C) 2014 hakre <http://hakre.wordpress.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author hakre <http://hakre.wordpress.com>
 * @license AGPL-3.0 <http://spdx.org/licenses/AGPL-3.0>
 */

class XMLReaderTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @dataProvider provideAllFiles
     */
    function readBehavior($file)
    {
        $reader = new XMLReaderStub($file);

        $it       = new XMLReaderIterator($reader);
        $expected = array();
        while ($reader->read()) {
            $expected[] = XMLReaderNode::dump($reader, true);
        }

        $reader->rewind();
        $index = 0;
        foreach ($it as $index => $node) {
            $this->assertEquals($expected[$index], XMLReaderNode::dump($reader, true));
        }

        $this->assertCount($index + 1, $expected);
    }

    /**
     * @test
     * @dataProvider provideAllFiles
     */
    function nextBehavior($file)
    {
        $reader = new XMLReaderStub($file);

        $it       = new XMLReaderNextIteration($reader);
        $expected = array();
        while ($reader->next()) {
            $expected[] = XMLReaderNode::dump($reader, true);
        }

        $reader->rewind();
        $index = 0;
        foreach ($it as $index => $node) {
            $this->assertEquals($expected[$index], XMLReaderNode::dump($reader, true));
        }

        $this->assertCount($index + 1, $expected);
    }

    /**
     * @see readBahvior
     * @see writeBehavior
     */
    public function provideAllFiles()
    {
        $result = array();

        $path   = __DIR__ . '/../fixtures';
        $result = $this->addXmlFiles($result, $path);

        $path   = __DIR__ . '/../../examples/data';
        $result = $this->addXmlFiles($result, $path);

        return $result;
    }

    private function addXmlFiles(array $result, $path)
    {
        /** @var FilesystemIterator|SplFileInfo[] $dir */
        $dir = new FilesystemIterator($path);
        foreach ($dir as $file) {
            $file->getExtension() === 'xml' && $result[] = array((string)$file);
        }

        return $result;
    }
}
