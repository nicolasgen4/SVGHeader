<?php

declare(strict_types=1);

/**
 * Handles SVG attributes and displays the SVG
 * @author: Nicolas LOIZEAU
 */
class SVGHeader
{
    private \SimpleXMLElement $svg;
    private object $attributes;
    private object $tags;
    private object $tagAttributes;
    private string $content;

    /*=======================================
      1. Loading & Checks
    ========================================*/
    /**
     * verify: Checks if the file exists and if it's an SVG
     * @param string $svg
     * @return void
     */
    private static function verify(string $svg): void
    {
        if (!file_exists($svg)) {
            die('Resource not found: ' . $svg);
        }
        if (strpos(mime_content_type($svg), 'image/svg') === false) {
            die('Not an SVG file: ' . $svg);
        }
    }

    /**
     * returnRaw: Returns the raw content of the SVG file
     * @param string $svg : SVG file path
     * @return string : SVG content
     */
    public static function returnRaw(string $svg): string
    {
        self::verify($svg);
        return file_get_contents($svg);
    }

    /**
     * __construct: Instantiates the SVGHeader class
     * @param string $svg : SVG file path
     */
    public function __construct(string $svg)
    {
        $svg = self::returnRaw($svg);
        $this->svg = new \SimpleXMLElement($svg);

        // <svg> tag attributes
        $attributes = [];
        foreach ($this->svg->attributes() as $key => $value) {
            $attributes[$key] = (string) $value;
        }
        $this->attributes = (object) $attributes;

        // Raw content inside <svg>
        $svg = str_ireplace('</svg>', '', $svg);
        $this->content = substr($svg, strpos($svg, '>') + 1);

        // Internal tags & their attributes
        $tags = [];
        $tagAttributes = [];

        foreach ($this->svg->children() as $key => $value) {
            $tags[$key] = (string) $value;
            foreach ($value->attributes() as $attr => $val) {
                $tagAttributes[$attr] = (string) $val;
            }
        }

        $this->tags = (object) $tags;
        $this->tagAttributes = (object) $tagAttributes;
    }

    /*=======================================
      2. <svg> Attributes
    ========================================*/
    /**
     * getAttributes: Returns <svg> attributes or a specific one
     * @param string|null $attribute
     * @return array|string|null
     */
    private function getAttributes(?string $attribute = null)
    {
        return is_null($attribute) ? get_object_vars($this->attributes) : ($this->attributes->$attribute ?? null);
    }

    /**
     * setAttribute: Sets a <svg> attribute, overwriting if it already exists
     * @param string $attribute
     * @param string $value
     * @return void
     */
    private function setAttribute(string $attribute, string $value): void
    {
        $this->attributes->$attribute = $value;
    }

    /**
     * removeAttribute: Removes a <svg> attribute if it exists
     * @param string $attribute 
     * @return void
     */
    private function removeAttribute(string $attribute): void
    {
        if (isset($this->attributes->$attribute)) {
            unset($this->attributes->$attribute);
        }
    }

    /**
     * cleanHeader: Removes all <svg> attributes except viewBox
     * @return void
     */
    public function cleanHeader(): void
    {
        foreach ($this->getAttributes() as $key => $value) {
            if ($key !== 'viewBox') unset($this->attributes->$key);
        }
    }

    /**
     * setClass: Sets the class attribute of the <svg> (replaces existing)
     * @param string $class
     * @return void
     */
    public function setClass(string $class): void
    {
        $this->setAttribute('class', $class);
    }

    /**
     * addClass: Appends a class to the existing <svg> classes
     * @param string $class
     * @return void
     */
    public function addClass(string $class): void
    {
        $existing = $this->getAttributes('class') ?? '';
        $this->setAttribute('class', trim($existing . ' ' . $class));
    }

    /**
     * setId: Sets the id attribute of the <svg>
     * @param string $id
     * @return void
     */
    public function setId(string $id): void
    {
        $this->setAttribute('id', $id);
    }

    /**
     * setColor: Sets the fill color of the <svg> (only if valid hex)
     * @param string $color
     * @return void
     */
    public function setColor(string $color): void
    {
        if (preg_match('/^#([0-9a-f]{3}){1,2}$/i', $color)) {
            $this->setAttribute('fill', $color);
        }
    }

    /**
     * resize: Adds width and height attributes to the <svg>
     * @param string $width
     * @param string $height
     * @return void
     */
    public function resize(string $width, string $height): void
    {
        if (!is_null($width)) $this->setAttribute('width', $width);
        if (!is_null($height)) $this->setAttribute('height', $height);
    }

    /*=======================================
      3. Internal Tags
    ========================================*/
    /**
     * setTag: Sets a new internal tag in <svg>
     * @param string $tag
     * @param string $value
     * @return void
     */
    private function setTag(string $tag, string $value): void
    {
        $this->tags->$tag = $value;
    }

    /**
     * getTags: Returns internal <svg> tags or a specific one
     * @param string|null $tag
     * @return array|string|null
     */
    private function getTags(?string $tag = null)
    {
        return is_null($tag) ? get_object_vars($this->tags) : ($this->tags->$tag ?? null);
    }

    /**
     * setTitle: Adds a <title> tag
     * @param string $title
     * @return void
     */
    public function setTitle(string $title): void
    {
        $this->setTag('title', $title);
    }

    /**
     * setLink: Wraps the <svg> in an <a> tag
     * @param string $link
     * @param string $href
     * @param string|null $class
     * @return void
     */
    public function setLink(string $link, string $href, ?string $class = null): void
    {
        $this->setTag('a', $link);
        $this->setTagAttribute('href', $href);
        if (!is_null($class)) {
            $this->setTagAttribute('class', $class);
        }
    }

    /*=======================================
      4. Internal Tag Attributes
    ========================================*/
    /**
     * setTagAttribute: Sets an attribute for a new internal tag
     * @param string $attribute
     * @param string|null $value
     * @return void
     */
    private function setTagAttribute(string $attribute, ?string $value = null): void
    {
        $this->tagAttributes->$attribute = $value;
    }

    /**
     * getTagAttributes: Returns internal tag attributes or a specific one
     * @param string|null $attribute
     * @return array|string|null
     */
    private function getTagAttributes(?string $attribute = null)
    {
        return is_null($attribute) ? get_object_vars($this->tagAttributes) : ($this->tagAttributes->$attribute ?? null);
    }

    /*=======================================
      5. Rendering & Saving
    ========================================*/
    /**
     * render: Outputs the final SVG content
     * @return string
     */
    public function render(): string
    {
        $svg = '';

        if (isset($this->tags->a)) {
            $link = $this->getTags('a');
            $href = $this->getTagAttributes('href') ?? '#';
            $class = $this->getTagAttributes('class') ?? '';
            $svg .= '<a href="' . $href . '"><span class="' . $class . '">' . $link . '</span>';
        }

        $svg .= '<svg';
        foreach ($this->getAttributes() as $key => $value) {
            $svg .= ' ' . $key . '="' . $value . '"';
        }
        $svg .= ' xmlns="http://www.w3.org/2000/svg">';

        if (isset($this->tags->title)) {
            $svg .= '<title>' . $this->getTags('title') . '</title>';
        }

        $svg .= $this->content;
        $svg .= '</svg>';

        if (isset($this->tags->a)) {
            $svg .= '</a>';
        }

        return $svg;
    }

    /**
     * save: Saves the SVG to a file
     * @param string $path
     * @return void
     */
    public function save(string $path): void
    {
        if (is_dir($path) && is_writable($path)) {
            $svg = $this->render();
            file_put_contents($path . '/svg_' . time() . '.svg', $svg);
            echo 'Success: SVG saved';
        }
    }

    /**
     * debug: Outputs debug information
     * @return void
     */
    public function debug(): void
    {
        echo "<pre>";
        print_r($this->getAttributes());
        print_r($this->getTags());
        print_r($this->getTagAttributes());
        echo "</pre>";
    }
}
