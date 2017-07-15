<?php

namespace markhuot\CraftQL\services;

use Craft;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;
use markhuot\CraftQL\Plugin;
use yii\base\Component;

class SchemaSectionService extends Component {

  private $sections = [];
  private $entries;
  private $elements;
  private $fields;

  function __construct(
    \markhuot\CraftQL\Services\SchemaEntryService $entries,
    \markhuot\CraftQL\Services\SchemaElementService $elements,
    \markhuot\CraftQL\Services\FieldService $fields
  ) {
    $this->entries = $entries;
    $this->elements = $elements;
    $this->fields = $fields;
  }

  function loadAllSections() {
    foreach (Craft::$app->sections->allSections as $section) {
      $this->sections[$section->handle] = $this->parseSectionToObject($section);
    }
  }

  function getSection($sectionHandle) {
    if (!isset($this->sections[$sectionHandle])) {
      $section = Craft::$app->sections->getSectionByHandle($sectionHandle);
      $this->sections[$sectionHandle] = $this->parseSectionToObject($section);
    }

    return $this->sections[$sectionHandle];
  }

  function loadedSections() {
    return $this->sections;
  }

  function parseSectionToObject($section) {
    $fields = $this->entries->baseFields();

    foreach ($section->entryTypes as $entryType) {
      $fields = array_merge($fields, $this->fields->getFields($entryType->fieldLayoutId));
    }

    return new ObjectType([
      'name' => ucfirst($section->handle),
      'fields' => $fields,
      'interfaces' => [
        $this->entries->getInterface(),
        $this->elements->getInterface(),
      ],
      'type' => $section->type,
    ]);
  }

  function getSectionArgs() {
    return [
      'after' => Type::string(),
      'ancestorOf' => Type::int(),
      'ancestorDist' => Type::int(),
      'archived' => Type::boolean(),
      'authorGroup' => Type::string(),
      'authorGroupId' => Type::int(),
      'authorId' => Type::int(),
      'before' => Type::string(),
      'level' => Type::int(),
      'localeEnabled' => Type::boolean(),
      'descendantOf' => Type::int(),
      'descendantDist' => Type::int(),
      'fixedOrder' => Type::boolean(),
      'id' => Type::int(),
      'limit' => Type::int(),
      'locale' => Type::string(),
      'nextSiblingOf' => Type::int(),
      'offset' => Type::int(),
      'order' => Type::string(),
      'positionedAfter' => Type::id(),
      'positionedBefore' => Type::id(),
      'postDate' => Type::string(),
      'prevSiblingOf' => Type::id(),
      'relatedTo' => Type::id(),
      'search' => Type::string(),
      'siblingOf' => Type::int(),
      'slug' => Type::string(),
      'status' => Type::string(),
      'title' => Type::string(),
      'type' => Type::string(),
      'uri' => Type::string(),
    ];
  }

  function getSectionField($handle) {
    $sectionType = $this->getSection($handle);
    $isSingle = $sectionType->config['type'] == 'single';

    return [
      $handle => [
        'type' => $isSingle ? $sectionType : Type::listOf($sectionType),
        'description' => 'Entries from the '.$handle.' section',
        'args' => $this->getSectionArgs(),
        'resolve' => function ($root, $args) use ($handle, $isSingle) {
            $criteria = \craft\elements\Entry::find();
            $criteria = $criteria->section($handle);
            foreach ($args as $key => $value) {
                $criteria = $criteria->{$key}($value);
            }
            return $isSingle ? $criteria->one() : $criteria->find();
        }
      ]
    ];
  }

}
