<?php

use App\Models\Setting;


it('should returns the first setting', function () {
    // create multiple settings
    $firstSetting = Setting::create([
        'allow_grades' => true,
        'school_in_template' => 'School In Template',
        'school_out_template' => 'School Out Template',
        'class_in_template' => 'Class Attendance In',
        'class_out_template' => 'Class Attendance Out',
    ]);

    Setting::create([
        'allow_grades' => false,
        'school_in_template' => 'Other In',
        'school_out_template' => 'Other Out',
        'class_in_template' => 'Other Class In',
        'class_out_template' => 'Other Class Out',
    ]);

    //  call the index endpoint
    $response = $this->getJson(route('settings.index'));

    // returns the first setting
    $response->assertOk()
        ->assertJson([
            'id' => $firstSetting->id,
            'allow_grades' => $firstSetting->allow_grades,
            'school_in_template' => $firstSetting->school_in_template,
            'school_out_template' => $firstSetting->school_out_template,
            'class_in_template' => $firstSetting->class_in_template,
            'class_out_template' => $firstSetting->class_out_template,
        ]);
});


it("should update allow_grades only", function () {
    $setting = Setting::create([
        'allow_grades' => true,
        'school_in_template' => 'School In Template',
        'school_out_template' => 'School Out Template',
        'class_in_template' => 'Class Attendance In',
        'class_out_template' => 'Class Attendance Out',
    ]);

    $data = ['allow_grades' => false];

    $response = $this->putJson(route('settings.update', $setting->id), $data);

    $response->assertOk()
        ->assertJsonFragment($data);

    $this->assertDatabaseHas('settings', array_merge(
        ['id' => $setting->id],
        $data
    ));
});

it("should update school_in_template only", function () {
    $setting = Setting::create([
        'allow_grades' => true,
        'school_in_template' => 'School In Template',
        'school_out_template' => 'School Out Template',
        'class_in_template' => 'Class Attendance In',
        'class_out_template' => 'Class Attendance Out',
    ]);

    $data = ['school_in_template' => 'Other In'];

    $response = $this->putJson(route('settings.update', $setting->id), $data);

    $response->assertOk()
        ->assertJsonFragment($data);

    $this->assertDatabaseHas('settings', array_merge(
        ['id' => $setting->id],
        $data
    ));
});
