<?php

use mindplay\heyloyalty\HeyLoyaltyClient;
use mindplay\heyloyalty\HeyLoyaltyField;
use mindplay\heyloyalty\HeyLoyaltyList;
use mindplay\heyloyalty\HeyLoyaltyListFilter;
use mindplay\heyloyalty\HeyLoyaltyMediator;
use mindplay\heyloyalty\HeyLoyaltyMember;

require __DIR__ . '/_header.php';

if (! file_exists(__DIR__ . '/config.local.php')) {
    throw new RuntimeException("missing config.local.php - please create it to run the test-suite");
}

require __DIR__ . '/config.local.php';

$client = new HeyLoyaltyClient(HEY_LOYALTY_API_KEY, HEY_LOYALTY_API_SECRET);

$EXPECTED_FIXED_FIELDS = array(
    'firstname',
    'lastname',
    'email',
    'mobile',
    'sex',
    'birthdate',
    'address',
    'postalcode',
    'city',
    'country',
    'password',
    'reference',
);

$EXPECTED_CUSTOM_FIELDS = array(
    'text',
    'number',
    'date',
    'boolean',
    'choice',
    'multi',
);

$ALL_EXPECTED_FIELDS = array_merge($EXPECTED_FIXED_FIELDS, $EXPECTED_CUSTOM_FIELDS);

test(
    'Can get List information',
    function () use ($client, $ALL_EXPECTED_FIELDS, $EXPECTED_FIXED_FIELDS, $EXPECTED_CUSTOM_FIELDS) {

        $list = $client->getList(HEY_LOYALTY_LIST_ID);

        eq($list->id, HEY_LOYALTY_LIST_ID);
        eq($list->name, 'DEV - Rasmus Test');
        eq($list->date_format, 'dd-mm-yyyy');
        eq($list->duplicates, HeyLoyaltyList::DUPLICATES_DISALLOW);

        eq(count($list->fields), count($ALL_EXPECTED_FIELDS));

        foreach ($ALL_EXPECTED_FIELDS as $expected_field) {
            ok(isset($list->fields[$expected_field]), "field {$expected_field} exists");
        }

        foreach ($EXPECTED_FIXED_FIELDS as $field_name) {
            ok($list->fields[$field_name]->type === HeyLoyaltyField::TYPE_FIXED, "field {$field_name} is fixed");
        }

        foreach ($EXPECTED_CUSTOM_FIELDS as $field_name) {
            ok($list->fields[$field_name]->type === HeyLoyaltyField::TYPE_CUSTOM, "field {$field_name} is custom");
        }

    }
);

/**
 * This class is used for IDE type-hinting for custom fields.
 *
 * @property string $text
 * @property int $number
 * @property int $date
 * @property bool $boolean
 * @property int $choice
 * @property int[] $multi
 */
abstract class TestMember extends HeyLoyaltyMember {}

test(
    'Can mediate between native PHP and Hey Loyalty API field values',
    function () use ($client, $ALL_EXPECTED_FIELDS) {

        /** @var TestMember $member */
        $member = $client->getListMemberByEmail(HEY_LOYALTY_LIST_ID, 'rasc@fynskemedier.dk');

        ok($member !== null, 'precondition: member found');

        ok(is_string($member->text), 'handles text fields');
        ok(is_int($member->number), 'handles number fields');
        ok(is_int($member->date), 'handles date fields');
        ok(is_bool($member->boolean), 'handles bool fields');
        ok(is_int($member->choice), 'handles choice fields');

        foreach ($member->multi as $value) {
            ok(is_int($value), "handles multi-value fields (#{$value})");
        }

    }
);

test(
    'Testing',
    function () use ($client) {

        #$list = $client->getList(HEY_LOYALTY_LIST_ID);
        #var_dump($list);

        #var_dump($client->getLists());

        #var_dump($client->getMember(HEY_LOYALTY_LIST_ID, '08539079-ed39-49b5-9b87-ad7651bd579f'));

    }
);

exit(status()); // exits with errorlevel (for CI tools etc.)
