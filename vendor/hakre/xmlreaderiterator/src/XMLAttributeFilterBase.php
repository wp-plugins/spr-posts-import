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

/**
 * Class XMLAttributeFilterBase
 */
abstract class XMLAttributeFilterBase extends XMLReaderFilterBase
{
    private $attr;

    /**
     * @param XMLElementIterator $elements
     * @param string $attr name of the attribute, '*' for every attribute
     */
    public function __construct(XMLElementIterator $elements, $attr)
    {
        parent::__construct($elements);
        $this->attr = $attr;
    }

    protected function getAttributeValues()
    {
        /* @var $node XMLReaderNode */
        $node = parent::current();
        if ('*' === $this->attr) {
            $attrs = $node->getAttributes()->getArrayCopy();
        } else {
            $attrs = (array) $node->getAttribute($this->attr);
        }

        return $attrs;
    }
}
