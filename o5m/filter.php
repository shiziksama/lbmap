<?php
include __DIR__.'/../vendor/autoload.php';
$z = new WeblamasXMLReader();
$z->open("php://stdin");
echo "<?xml version='1.0' encoding='UTF-8'?>".PHP_EOL;

while($z->read()) {
    if ($z->name == 'node' && $z->nodeType == \XmlReader::ELEMENT) {
        $id = $z->getAttribute('id');
        $lat = $z->getAttribute('lat');
        $lon = $z->getAttribute('lon');
        echo '<node id="' . $id . '" lat="' . $lat . '" lon="' . $lon . '"/>' . PHP_EOL;
        $z->endElement();
        continue;
    } elseif ($z->name == 'way' && $z->nodeType == \XmlReader::ELEMENT) {
        $node = simplexml_load_string($z->readOuterXml());
        $nodes = [];
        $tags = [];

        foreach ($node as $key => $child) {
            if ($key == 'nd') {
                $nodes[] = (string)$child->attributes()->ref;
            } elseif ($key == 'tag') {
                $tags[(string)$child->attributes()->k] = (string)$child->attributes()->v;
            }
        }

        if (empty($tags['highway'])) {
            $z->endElement();
            continue;
        }

        $tags = OsmFilter::modify_tags($tags);
        $result = OsmFilter::test_element(['tags' => $tags]);

        if ($result == 'no') {
            $z->endElement();
            continue;
        } elseif (in_array($result, ['great', 'bicycle_undefined', 'bikelane', 'greatfoot', 'foot'])) {
            $tags = ['lbroads' => $result, 'highway' => 'lbroad'];
        }

        echo '<way id="' . $node->attributes()->id . '">' . PHP_EOL;
        foreach ($nodes as $nd) {
            echo '<nd ref="' . $nd . '"/>' . PHP_EOL;
        }
        foreach ($tags as $k => $v) {
            echo '<tag k="' . $k . '" v="' . $v . '"/>' . PHP_EOL;
        }
        echo '</way>' . PHP_EOL;

        unset($node, $nodes, $tags);
        $z->endElement();
    } else {
        if ($z->nodeType == \XmlReader::ELEMENT) {
            $attributes = '';
            $name = $z->name;
            if ($z->hasAttributes) {
                while ($z->moveToNextAttribute()) {
                    $attributes .= ' ' . $z->name . '="' . $z->value . '"';
                }
            }
            echo '<' . $name . $attributes . '>' . PHP_EOL;
        } elseif ($z->nodeType == \XmlReader::END_ELEMENT) {
            echo '</' . $z->name . '>' . PHP_EOL;
        }
    }
}
echo '</xml>';