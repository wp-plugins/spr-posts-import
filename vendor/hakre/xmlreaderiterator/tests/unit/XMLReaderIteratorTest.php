<?php
/*
 * This file is part of the XMLReaderIterator package.
 *
 * Copyright (C) 2012, 2013 hakre <http://hakre.wordpress.com>
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

class XMLReaderIteratorTest extends PHPUnit_Framework_TestCase {

    /**
     * @test
     */
    function iteration() {
        $reader = new XMLReaderStub('<r><a>1</a><a>2</a></r>');

        $it = new XMLReaderIterator($reader);
        $this->assertSame(null, $it->valid());

        $it->rewind();
        $this->assertSame(true, $it->valid());

        $node = $it->current();
        $this->assertEquals('r', $node->getName());
        $this->assertEquals('12', (string) $node);

        $it->moveToNextElementByName('a');
        $node = $it->current();
        $this->assertEquals('a', $node->getName());
        $this->assertEquals('1', (string) $node);

        $it->moveToNextElementByName('a');
        $node = $it->current();
        $this->assertEquals('a', $node->getName());
        $this->assertEquals('2', (string) $node);

        $it->next();
        $it->next();
        $this->assertEquals(XMLReader::END_ELEMENT, $reader->nodeType);
        $this->assertEquals('a', $it->current()->getName());

        $it->next();
        $it->next();
        $this->assertEquals(false, $it->valid());
    }
}
