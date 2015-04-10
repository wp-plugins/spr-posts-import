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
 * Class XMLReaderNode
 */
class XMLReaderNode implements XMLReaderAggregate
{
    public $name;
    private $reader;
    private $nodeType;
    private $string;
    private $attributes;
    private $simpleXML;

    public function __construct(XMLReader $reader)
    {
        $this->reader         = $reader;
        $this->nodeType       = $reader->nodeType;
        $this->name           = $reader->name;
    }

    public function __toString()
    {
        if (null === $this->string) {
            $this->string = $this->readString();
        }

        return $this->string;
    }

    /**
     * SimpleXMLElement for XMLReader::ELEMENT
     *
     * @return SimpleXMLElement|null in case the current node can not be converted into a SimpleXMLElement
     * @since 0.1.4
     */
    public function getSimpleXMLElement()
    {
        if (null === $this->simpleXML)
        {
            if ($this->reader->nodeType !== XMLReader::ELEMENT) {
                return null;
            }

            $node = $this->getDocumentNode();
            $this->simpleXML = simplexml_import_dom($node);
        }

        return $this->simpleXML;
    }

    /**
     * Alias of @see getSimpleXMLElement()
     *
     * @return null|SimpleXMLElement
     */
    public function asSimpleXML()
    {
        trigger_error('Deprecated ' . __METHOD__ . '() - use getSimpleXMLElement() in the future', E_USER_NOTICE);
        return $this->getSimpleXMLElement();
    }

    /**
     * @return XMLAttributeIterator|XMLReaderNode[]
     */
    public function getAttributes()
    {
        if (null === $this->attributes) {
            $this->attributes = new XMLAttributeIterator($this->reader);
        }

        return $this->attributes;
    }

    /**
     * @param string $name    attribute name
     * @param string $default (optional) if the attribute with $name does not exists, the value to return
     *
     * @return null|string value of the attribute, if attribute with $name does not exists null (by $default)
     */
    public function getAttribute($name, $default = null)
    {
        $value = $this->reader->getAttribute($name);

        return null !== $value ? $value : $default;
    }

    /**
     * @param string $name           (optional) element name, null or '*' stand for each element
     * @param bool   $descendantAxis descend into children of children and so on?
     *
     * @return XMLChildElementIterator|XMLReaderNode[]
     */
    public function getChildElements($name = null, $descendantAxis = false)
    {
        return new XMLChildElementIterator($this->reader, $name, $descendantAxis);
    }

    /**
     * @return XMLChildIterator|XMLReaderNode[]
     */
    public function getChildren()
    {
        return new XMLChildIterator($this->reader);
    }

    public function getName()
    {
        return $this->name;
    }

    public function getReader()
    {
        return $this->reader;
    }

    /**
     * Decorated method
     *
     * @throws BadMethodCallException in case XMLReader can not expand the node
     * @return string
     */
    public function readOuterXml()
    {
        // Compat libxml 20620 (2.6.20) or later - LIBXML_VERSION  / LIBXML_DOTTED_VERSION
        if (method_exists($this->reader, 'readOuterXml')) {
            return $this->reader->readOuterXml();
        }

        if (0 === $this->reader->nodeType) {
            return '';
        }

        $node = $this->getDocumentNode();

        /**
         * FIXME this var hint is un-necessary
         *
         * @link http://youtrack.jetbrains.com/issue/WI-23810
         *
         * @var $doc DOMDocument
         */
        $doc  = $node->ownerDocument;
        $doc->formatOutput = true;
        $node = $doc->appendChild($node);
        return $doc->saveXML($node);
    }

    /**
     * XMLReader expand node and import it into a DOMNode with a DOMDocument
     *
     * This is for example useful for DOMDocument::saveXML() @see readOuterXml
     * or getting a SimpleXMLElement out of it @see getSimpleXMLElement
     *
     * @throws BadMethodCallException
     *
     * @return DOMNode
     */
    private function getDocumentNode() {
        if (false === $node = $this->reader->expand()) {
            throw new BadMethodCallException('Unable to expand node.');
        }

        $doc  = new DomDocument();
        $node = $doc->importNode($node, true);

        return $node;
    }

    /**
     * Decorated method
     *
     * @throws BadMethodCallException
     * @return string
     */
    public function readString()
    {
        // Compat libxml 20620 (2.6.20) or later - LIBXML_VERSION  / LIBXML_DOTTED_VERSION
        if (method_exists($this->reader, 'readString')) {
            return $this->reader->readString();
        }

        if (0 === $this->reader->nodeType) {
            return '';
        }

        if (false === $node = $this->reader->expand()) {
            throw new BadMethodCallException('Unable to expand node.');
        }

        return $node->textContent;
    }

    /**
     * Return Nodetype as human readable string (constant name)
     *
     * @param null $nodeType
     *
     * @return string
     */
    public function getNodeTypeName($nodeType = null)
    {
        $strings = array(
            XMLReader::NONE                   => 'NONE',
            XMLReader::ELEMENT                => 'ELEMENT',
            XMLReader::ATTRIBUTE              => 'ATTRIBUTE',
            XMLREADER::TEXT                   => 'TEXT',
            XMLREADER::CDATA                  => 'CDATA',
            XMLReader::ENTITY_REF             => 'ENTITIY_REF',
            XMLReader::ENTITY                 => 'ENTITY',
            XMLReader::PI                     => 'PI',
            XMLReader::COMMENT                => 'COMMENT',
            XMLReader::DOC                    => 'DOC',
            XMLReader::DOC_TYPE               => 'DOC_TYPE',
            XMLReader::DOC_FRAGMENT           => 'DOC_FRAGMENT',
            XMLReader::NOTATION               => 'NOTATION',
            XMLReader::WHITESPACE             => 'WHITESPACE',
            XMLReader::SIGNIFICANT_WHITESPACE => 'SIGNIFICANT_WHITESPACE',
            XMLReader::END_ELEMENT            => 'END_ELEMENT',
            XMLReader::END_ENTITY             => 'END_ENTITY',
            XMLReader::XML_DECLARATION        => 'XML_DECLARATION',
        );

        if (null === $nodeType) {
            $nodeType = $this->nodeType;
        }

        return $strings[$nodeType];
    }

    /**
     * decorate method calls
     */
    public function __call($name, $args)
    {
        return call_user_func_array(array($this->reader, $name), $args);
    }

    /**
     * decorate property get
     */
    public function __get($name)
    {
        return $this->reader->$name;
    }

    /**
     * debug utility method
     *
     * @param XMLReader $reader
     * @param bool $return (optional) prints by default but can return string
     * @return string
     */
    public static function dump(XMLReader $reader, $return = FALSE)
    {
        $node = new self($reader);

        $nodeType = $reader->nodeType;
        $nodeName = $node->getNodeTypeName();

        $extra = '';

        if ($reader->nodeType === XMLReader::ELEMENT) {
            $extra = '<' . $reader->name . '> ';
            $extra .= sprintf("(isEmptyElement: %s) ", $reader->isEmptyElement ? 'Yes' : 'No');
        }

        if ($reader->nodeType === XMLReader::END_ELEMENT) {
            $extra = '</' . $reader->name . '> ';
        }

        if ($reader->nodeType === XMLReader::ATTRIBUTE) {
            $str = $reader->value;
            $len = strlen($str);
            if ($len > 20) {
                $str = substr($str, 0, 17) . '...';
            }
            $str   = strtr($str, ["\n" => '\n']);
            $extra = sprintf('%s = (%d) "%s" ', $reader->name, strlen($str), $str);
        }

        if ($reader->nodeType === XMLReader::TEXT || $reader->nodeType === XMLReader::WHITESPACE || $reader->nodeType === XMLReader::SIGNIFICANT_WHITESPACE) {
            $str = $reader->readString();
            $len = strlen($str);
            if ($len > 20) {
                $str = substr($str, 0, 17) . '...';
            }
            $str   = strtr($str, ["\n" => '\n']);
            $extra = sprintf('(%d) "%s" ', strlen($str), $str);
        }

        $label = sprintf("(#%d) %s %s", $nodeType, $nodeName, $extra);

        if ($return) {
            return $label;
        }

        printf("%s%s\n", str_repeat('  ', $reader->depth), $label);
    }
}
