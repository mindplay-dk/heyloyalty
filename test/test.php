<?php

use mindplay\heyloyalty\HeyLoyaltyClient;
use mindplay\heyloyalty\HeyLoyaltyField;
use mindplay\heyloyalty\HeyLoyaltyList;
use mindplay\heyloyalty\HeyLoyaltyMember;

define('TEST_EMAIL', 'rasc-2@fynskemedier.dk');
define('TEST_MOBILE', '87654321');

require __DIR__ . '/_header.php';

if (!file_exists(__DIR__ . '/config.local.php')) {
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

$member = $client->getMemberByEmail(HEY_LOYALTY_LIST_ID, TEST_EMAIL);

if ($member instanceof HeyLoyaltyMember) {
    echo "Precondition failed: cleaning up member from previous failed test run.\n";

    $client->deleteMember($member);

    unset($member);
}


//TODO implement test
//test(
//    'Can enumerate lists',
//    function () use ($client) {
//        $lists = $client->getLists();
//
//        $valid = 0;
//
//        foreach ($lists as $list) {
//            if ($list instanceof HeyLoyaltyListInfo) {
//                $valid += 1;
//            }
//        }
//
//        ok($valid > 1, 'returns at least one valid list', $valid);
//
//        eq($valid, count($lists), 'all lists returned as objects');
//    }
//);

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
abstract class TestMember extends HeyLoyaltyMember
{
}

test(
    'Can mediate between native PHP and Hey Loyalty API field values',
    function () use ($client, $ALL_EXPECTED_FIELDS) {

        /** @var TestMember $member */
        $member = $client->getMemberByEmail(HEY_LOYALTY_LIST_ID, 'rasc@fynskemedier.dk');

        ok($member !== null, 'precondition: member found');

        ok(is_string($member->text), 'handles text fields');
        ok(is_int($member->number), 'handles number fields');
        ok(is_int($member->date), 'handles date fields');
        ok(is_bool($member->boolean), 'handles bool fields');
        ok(is_int($member->choice), 'handles choice fields');

        eq(count($member->multi), 2, 'handles multi-value fields (total = 2)');

        foreach ($member->multi as $value) {
            ok(is_int($value), "handles multi-value fields (#{$value})");
        }

    }
);

test(
    'Can create a new member',
    function () use ($client) {
        /** @var TestMember $member */
        $member = new HeyLoyaltyMember(HEY_LOYALTY_LIST_ID);

        /** @var TestMember $member */
        $member = $client->getMemberByEmail(HEY_LOYALTY_LIST_ID, 'rasc@fynskemedier.dk');

        ok($member !== null, 'precondition: member found');

        unset($member->id); // effectively gives us a clone of the existing member

        $member->mobile = TEST_MOBILE;
        $member->email = TEST_EMAIL;

        $member_id = $client->createMember($member);

        ok($member_id != '', 'member ID returned', $member_id);

        eq($member->id, $member_id, 'member ID applied to member');
    }
);

test(
    'Can update an existing member',
    function () use ($client) {
        $member = $client->getMemberByEmail(HEY_LOYALTY_LIST_ID, TEST_EMAIL);

        ok($member instanceof HeyLoyaltyMember, 'member found');

        $FIRST_NAME = 'Charlie';
        $LAST_NAME = 'Tuna';

        $member->firstname = $FIRST_NAME;
        $member->lastname = $LAST_NAME;

        $client->updateMember($member);

        unset($member);

        $member = $client->getMemberByMobile(HEY_LOYALTY_LIST_ID, TEST_MOBILE);

        ok($member instanceof HeyLoyaltyMember, 'member found');
        eq($member->firstname, $FIRST_NAME, 'firstname saved');
        eq($member->lastname, $LAST_NAME, 'lastname saved');
    }
);

test(
    'Can enumerate list members',
    function () use ($client) {
        $count = 0;

        $client->enumerateMembers(
            HEY_LOYALTY_LIST_ID,
            function (HeyLoyaltyMember $member) use (&$count) {
                $count += 1;
            }
        );

        eq($count, 2, 'enumerated all members');
    }
);

test(
    'Can delete a member',
    function () use ($client) {
        $member = $client->getMemberByEmail(HEY_LOYALTY_LIST_ID, TEST_EMAIL);

        ok($member instanceof HeyLoyaltyMember, 'precondition: member exists');

        $client->deleteMember($member);

        unset($member);

        $member = $client->getMemberByEmail(HEY_LOYALTY_LIST_ID, TEST_EMAIL);

        ok($member === null, 'member deleted');
    }
);

exit(status()); // exits with errorlevel (for CI tools etc.)
