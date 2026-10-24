<?php

namespace Database\Seeders;

use App\Models\FlowChart;
use App\Support\SopTemplate;
use Illuminate\Database\Seeder;

class DeveloperSetupFlowChartSeeder extends Seeder
{
    public function run(): void
    {
        $examples = [
            [
                'title' => 'Arduino CLI Setup & Upload',
                'subject_name' => 'Arduino CLI',
                'description' => 'Example workflow for installing Arduino CLI, configuring a board core, compiling a sketch, uploading it, and opening the serial monitor. Use the official guide at https://arduino.github.io/arduino-cli/latest/ for platform-specific installation steps.',
                'steps' => [
                    ['Start', 'Confirm the operating system, Arduino board, USB data cable, and sketch'],
                    ['Action', 'Install Arduino CLI using the official instructions for this operating system'],
                    ['Action', 'Initialize the CLI configuration and update the board index'],
                    ['Action', 'Install the core that matches the board, such as arduino:avr for supported Arduino boards'],
                    ['Action', 'Connect the board and list detected boards and serial ports'],
                    ['Decision', 'Is the board detected and is its port available?'],
                    ['Action', 'Install the correct USB driver, check the cable and permissions, then scan again'],
                    ['Action', 'Compile the sketch for the board FQBN and resolve any build errors'],
                    ['Decision', 'Did compilation succeed?'],
                    ['Action', 'Fix the sketch or select the correct core and FQBN, then compile again'],
                    ['Action', 'Upload the compiled sketch to the detected port'],
                    ['Decision', 'Did the upload complete successfully?'],
                    ['Action', 'Check the selected port, board, cable, and boot/reset procedure, then retry'],
                    ['Action', 'Open the serial monitor at the sketch baud rate and verify expected output'],
                    ['End', 'Record the CLI version, core, FQBN, port, and verified upload steps'],
                ],
                'connections' => [
                    [1], [2], [3], [4], [5], [6, 7], [4], [8], [10, 9], [7], [11], [13, 12], [10], [14], [],
                ],
            ],
            [
                'title' => 'ESP32 Setup with Arduino IDE',
                'subject_name' => 'ESP32 Arduino IDE',
                'description' => 'Example workflow for setting up an ESP32 development board in Arduino IDE using Espressif’s Arduino core. Follow https://docs.espressif.com/projects/arduino-esp32/en/latest/installing.html and use the Boards Manager package URL from Espressif’s official documentation.',
                'steps' => [
                    ['Start', 'Identify the ESP32 board variant, operating system, and USB-to-serial interface'],
                    ['Action', 'Install Arduino IDE and open Preferences or Settings'],
                    ['Action', 'Add the Espressif ESP32 Boards Manager URL from the official installation guide'],
                    ['Action', 'Open Boards Manager and install the esp32 platform published by Espressif Systems'],
                    ['Action', 'Connect the board with a USB data cable and install its supported USB serial driver if needed'],
                    ['Action', 'Select the matching ESP32 board and detected serial port'],
                    ['Action', 'Open a basic example or create a sketch with Serial.begin and a simple status message'],
                    ['Action', 'Compile the sketch and review any board-package or library errors'],
                    ['Decision', 'Did the sketch compile successfully?'],
                    ['Action', 'Confirm the selected ESP32 board and package, correct the error, and compile again'],
                    ['Action', 'Upload the sketch; use the board BOOT button only if the board requires it'],
                    ['Decision', 'Did upload finish and does the serial output match the expected baud rate?'],
                    ['Action', 'Check the USB cable, port, driver, board selection, and board-specific boot instructions'],
                    ['End', 'Save the verified board selection, package version, port notes, and working sketch'],
                ],
                'connections' => [
                    [1], [2], [3], [4], [5], [6], [7], [8], [10, 9], [7], [11], [13, 12], [10], [],
                ],
            ],
            [
                'title' => 'Laravel Project Setup',
                'subject_name' => 'Laravel application setup',
                'description' => 'Example local setup flow for a Laravel application. Check the Laravel documentation for the project’s supported PHP version and required extensions. Keep real credentials out of source control.',
                'steps' => [
                    ['Start', 'Check the project README for the supported PHP, Composer, database, and Node.js versions'],
                    ['Action', 'Install the required PHP extensions, Composer, database server, and frontend runtime'],
                    ['Action', 'Create the project with Composer or clone the existing repository'],
                    ['Action', 'Install PHP dependencies and frontend packages using the project lockfiles'],
                    ['Action', 'Copy the example environment file to .env and configure the local database connection'],
                    ['Action', 'Generate the application key and create the empty local database'],
                    ['Action', 'Run migrations and only the seeders appropriate for a local development environment'],
                    ['Action', 'Build frontend assets and start the local development server'],
                    ['Decision', 'Does the application load and can it connect to the configured database?'],
                    ['Action', 'Review application logs, database credentials, migrations, and missing dependencies'],
                    ['Action', 'Run the relevant automated tests and verify the first application workflow'],
                    ['End', 'Document local setup, required environment values, and safe deployment differences'],
                ],
                'connections' => [
                    [1], [2], [3], [4], [5], [6], [7], [8], [10, 9], [7], [11], [],
                ],
            ],
            [
                'title' => 'Database Tables & Relationship Example',
                'subject_name' => 'Laravel database relationships',
                'description' => 'Example of designing related Laravel tables and wiring foreign keys to Eloquent models. Uses projects and tasks for a one-to-many example, then outlines a pivot table for many-to-many links.',
                'steps' => [
                    ['Start', 'Choose the entities and relationship; example: one project has many tasks'],
                    ['Action', 'Create the parent projects table with its primary key'],
                    ['Action', 'Create the tasks table with a project_id foreign key using foreignId()->constrained()->cascadeOnDelete()'],
                    ['Action', 'Add a hasMany(Task::class) relationship on Project and belongsTo(Project::class) on Task'],
                    ['Action', 'Run the migration and inspect the database to confirm the foreign key and index'],
                    ['Action', 'Create related records through $project->tasks()->create(...) and load them through the relationship'],
                    ['Decision', 'Can the application query the parent and related rows with the expected ownership?'],
                    ['Action', 'Check migration order, key names and types, relationship foreign keys, and query constraints'],
                    ['Action', 'For many-to-many, create a pivot table with both foreign keys, unique pair, and belongsToMany relationships'],
                    ['Action', 'Test create, read, update, and delete behavior, including the intended cascade or restriction rules'],
                    ['End', 'Document the relationship, migration, model methods, example query, and tested deletion behavior'],
                ],
                'connections' => [
                    [1], [2], [3], [4], [5], [6], [8, 7], [4], [9], [10], [],
                ],
            ],
        ];

        foreach ($examples as $example) {
            $flowChart = FlowChart::query()->firstOrNew([
                'category' => 'process',
                'title' => $example['title'],
            ]);
            $flowChart->fill([
                'is_example' => true,
                'subject_name' => $example['subject_name'],
                'description' => $example['description'],
                'steps' => SopTemplate::customSteps(
                    $example['steps'],
                    $example['connections'],
                    $flowChart->steps ?? [],
                ),
            ])->save();
        }
    }
}
