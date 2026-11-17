<?php

namespace Database\Seeders;

use App\Models\MetaContentPost;
use App\Models\User;
use Illuminate\Database\Seeder;

class MetaContentDraftsSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'support@omgcomputer.my')->firstOrFail();

        $drafts = [
            [
                'platform' => 'facebook',
                'caption' => 'Slow laptop, frequent crashes, or a computer that will not start? Tell us what is happening and when it began. OMG Computer can help you discuss repair and upgrade options. Find out more at https://omgcomputer.my #ComputerRepair #OMGComputer',
            ],
            [
                'platform' => 'facebook',
                'caption' => 'Choosing a laptop for study or work? Start with the programs you use, how often you travel with it, and your budget. Message OMG Computer and we can help you compare suitable options and check current availability. #LaptopTips #OMGComputer',
            ],
            [
                'platform' => 'facebook',
                'caption' => 'Planning a desktop PC for work, study, or gaming? Share your budget, the apps or games you use, and the monitor resolution you want. We can help you plan a balanced build and check current parts availability. Contact OMG Computer to discuss a quote. #CustomPC #OMGComputer',
            ],
            [
                'platform' => 'facebook',
                'caption' => 'Need a website for your business? Tell us what you want it to do, from showing your services to collecting customer enquiries. OMG Computer can discuss a website project that fits your needs. Visit https://omgcomputer.my #WebDesign #SmallBusiness',
            ],
            [
                'platform' => 'tiktok',
                'caption' => '3 quick laptop care checks: keep the air vents clear, use it on a hard surface, and back up important files. If it keeps overheating or shutting down, get the cause checked instead of guessing at parts. #LaptopCare #TechTips #OMGComputer',
            ],
            [
                'platform' => 'tiktok',
                'caption' => 'SSD vs RAM: an SSD stores your files and programs; RAM helps your computer handle apps that are open. A slow PC can have different causes, so check compatibility before upgrading. #PCUpgrade #TechExplained #OMGComputer',
            ],
            [
                'platform' => 'tiktok',
                'caption' => 'Before buying a computer, ask: What will I use it for? Which apps or games matter most? Do I need to carry it every day? Answering those questions makes it easier to compare options. #LaptopBuyingTips #OMGComputer',
            ],
        ];

        foreach ($drafts as $draft) {
            MetaContentPost::query()->firstOrCreate(
                [
                    'created_by' => $admin->id,
                    'platform' => $draft['platform'],
                    'caption' => $draft['caption'],
                ],
                [
                    'media_type' => 'text',
                    'status' => 'draft',
                ],
            );
        }
    }
}
