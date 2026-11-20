<?php

namespace Database\Seeders;

use App\Models\Project;
use Illuminate\Database\Seeder;

class CafePosProjectSeeder extends Seeder
{
    public function run(): void
    {
        $project = Project::query()->firstOrCreate(
            ['name' => 'Cafe POS System'],
            [
                'description' => 'Plan a point-of-sale system for cafe counter service, order handling, and sales reporting.',
                'planning_status' => 'planning',
            ],
        );

        foreach ([
            'description' => 'Plan a point-of-sale system for cafe counter service, order handling, and sales reporting.',
            'requirements_notes' => 'Confirm the cafe operating model, tax and service-charge rules, receipt needs, payment providers, and local compliance requirements with the cafe owner before implementation. Do not store payment-card details.',
            'business_background' => 'Cafe staff need a consistent way to take orders, communicate with the kitchen, accept payments, and reconcile sales.',
            'user_roles' => "Owner: configure the cafe and review reports.\nManager: manage menu, staff access, and approved adjustments.\nCashier: take orders, accept payments, and issue receipts.\nKitchen staff: view and update order preparation status.",
            'objectives' => "Reduce order and payment-entry errors.\nSpeed up counter service and kitchen communication.\nGive the owner accurate sales and payment summaries.",
            'problem_statement' => 'Paper tickets and manual sales records make it difficult to keep orders accurate, communicate changes, and reconcile daily payments.',
            'proposed_solution' => 'Build a touch-friendly cafe POS that manages menu items, dine-in and takeaway orders, payment records, kitchen tickets, and sales reporting.',
            'scope_in' => 'Menu and availability management; dine-in and takeaway orders; configurable charges and discounts; payment recording; receipts and kitchen tickets; manager-approved voids and refunds; sales reports; basic ingredient stock alerts.',
            'scope_out' => 'Payroll, accounting, delivery-marketplace integrations, multi-branch operations, and storage of payment-card data are excluded from the initial release.',
        ] as $field => $value) {
            if (blank($project->getAttribute($field))) {
                $project->setAttribute($field, $value);
            }
        }

        if ($project->isDirty()) {
            $project->save();
        }

        $requirements = [
            [
                'code' => 'BR-01',
                'requirement_type' => 'business',
                'priority' => 'must',
                'requirement' => 'The cafe can record orders and sales through one POS workflow, reducing manual order and payment tracking.',
                'user_story' => 'As the cafe owner, I want orders and sales recorded in one system so daily operations do not depend on paper notes.',
                'acceptance_criteria' => 'Each completed sale has an order record, item details, totals, payment method, and timestamp.',
            ],
            [
                'code' => 'BR-02',
                'requirement_type' => 'business',
                'priority' => 'must',
                'requirement' => 'The owner can review sales and payment totals to support daily reconciliation and operating decisions.',
                'user_story' => 'As the owner, I want to compare sales by date and payment method so I can reconcile the cafe takings.',
                'acceptance_criteria' => 'A date-based report shows order count, gross sales, discounts, refunds, and totals by payment method.',
            ],
            [
                'code' => 'BR-03',
                'requirement_type' => 'business',
                'priority' => 'must',
                'requirement' => 'Price changes, discounts, voids, and refunds are limited to authorized staff and can be reviewed.',
                'user_story' => 'As the owner, I want sensitive sales changes controlled and recorded so I can review exceptions.',
                'acceptance_criteria' => 'Restricted actions require manager permission and record the actor, time, reason, and affected order.',
            ],
            [
                'code' => 'FR-01',
                'requirement_type' => 'functional',
                'priority' => 'must',
                'requirement' => 'An authorized manager can manage menu categories, items, prices, options, and availability.',
                'user_story' => 'As a manager, I want to maintain the menu so cashiers can only sell current items at approved prices.',
                'acceptance_criteria' => 'Managers can add, edit, and deactivate menu entries; unavailable items cannot be added to new orders.',
            ],
            [
                'code' => 'FR-02',
                'requirement_type' => 'functional',
                'priority' => 'must',
                'requirement' => 'A cashier can create dine-in and takeaway orders with quantities, item options, notes, and a table or collection identifier.',
                'user_story' => 'As a cashier, I want to capture the order type and item details so the kitchen receives clear instructions.',
                'acceptance_criteria' => 'The cashier can add, change, and remove items before payment; dine-in orders identify a table and takeaway orders identify a collection order.',
            ],
            [
                'code' => 'FR-03',
                'requirement_type' => 'functional',
                'priority' => 'must',
                'requirement' => 'The POS calculates item totals, discounts, tax, and service charges using manager-configured rules.',
                'user_story' => 'As a cashier, I want to see a correct total and its breakdown before collecting payment.',
                'acceptance_criteria' => 'The order shows subtotal, each discount or charge, and final total; configured rates can be changed by an authorized manager.',
            ],
            [
                'code' => 'FR-04',
                'requirement_type' => 'functional',
                'priority' => 'must',
                'requirement' => 'A cashier can record cash, card, or e-wallet payment and issue a receipt without storing card details.',
                'user_story' => 'As a cashier, I want to record the confirmed tender and provide a receipt so each order has a clear payment record.',
                'acceptance_criteria' => 'The payment method and amount are recorded; an order is marked paid only after confirmation; no card number or security code is stored.',
            ],
            [
                'code' => 'FR-05',
                'requirement_type' => 'functional',
                'priority' => 'should',
                'requirement' => 'The kitchen can view order tickets and update preparation status.',
                'user_story' => 'As kitchen staff, I want to see new orders and mark their progress so the counter can give accurate updates.',
                'acceptance_criteria' => 'A new accepted order appears with its items, options, notes, and order identifier; staff can mark it preparing and ready.',
            ],
            [
                'code' => 'FR-06',
                'requirement_type' => 'functional',
                'priority' => 'must',
                'requirement' => 'An authorized manager can void an order or record a refund with a reason.',
                'user_story' => 'As a manager, I want to correct an exceptional sale while retaining an audit record.',
                'acceptance_criteria' => 'A void or refund requires permission and a reason; the original sale remains visible in reports with its adjusted status.',
            ],
            [
                'code' => 'FR-07',
                'requirement_type' => 'functional',
                'priority' => 'should',
                'requirement' => 'The owner can view and export sales reports by date range, menu item, order type, and payment method.',
                'user_story' => 'As the owner, I want reports that explain what sold and how customers paid so I can plan cafe operations.',
                'acceptance_criteria' => 'Authorized users can filter the report by date range and export the displayed summary as CSV.',
            ],
            [
                'code' => 'FR-08',
                'requirement_type' => 'functional',
                'priority' => 'could',
                'requirement' => 'The cafe can track selected ingredient stock and receive a low-stock warning.',
                'user_story' => 'As a manager, I want to monitor selected ingredients so I can plan replenishment before stock runs out.',
                'acceptance_criteria' => 'A manager can set a stock threshold and sees a warning when a tracked ingredient reaches or falls below it.',
            ],
            [
                'code' => 'NFR-01',
                'requirement_type' => 'non_functional',
                'priority' => 'must',
                'requirement' => 'The system enforces role-based access and protects order and customer information.',
                'user_story' => 'As the owner, I want each staff member to have only the access needed for their role.',
                'acceptance_criteria' => 'Owner, manager, cashier, and kitchen permissions are distinct; payment-card data is not retained by the POS.',
            ],
            [
                'code' => 'NFR-02',
                'requirement_type' => 'non_functional',
                'priority' => 'should',
                'requirement' => 'The checkout interface is usable on the cafe counter tablet and desktop display.',
                'user_story' => 'As a cashier, I want clear, touch-friendly controls so I can operate the POS during busy service.',
                'acceptance_criteria' => 'Core order and payment actions are readable and operable on supported tablet and desktop sizes, with keyboard access for primary actions.',
            ],
            [
                'code' => 'NFR-03',
                'requirement_type' => 'non_functional',
                'priority' => 'should',
                'requirement' => 'Common menu and checkout actions respond within two seconds under the agreed test workload.',
                'user_story' => 'As a cashier, I want order entry and checkout to remain responsive during normal service.',
                'acceptance_criteria' => 'With the agreed sample menu and test workload, menu loading, order updates, and total calculation each complete within two seconds.',
            ],
            [
                'code' => 'NFR-04',
                'requirement_type' => 'non_functional',
                'priority' => 'must',
                'requirement' => 'Order and payment records are backed up and can be restored.',
                'user_story' => 'As the owner, I want to recover business records after an operational or technical failure.',
                'acceptance_criteria' => 'Backups run at the agreed frequency, are access-controlled, and a documented restore test succeeds before launch.',
            ],
            [
                'code' => 'NFR-05',
                'requirement_type' => 'non_functional',
                'priority' => 'must',
                'requirement' => 'Retrying a payment or order submission does not create duplicate completed sales.',
                'user_story' => 'As a cashier, I want safe retries when a connection is interrupted so customers are not charged twice.',
                'acceptance_criteria' => 'Repeated submission of the same checkout attempt results in at most one completed sale and one corresponding payment record.',
            ],
        ];

        foreach ($requirements as $sortOrder => $requirement) {
            $code = $requirement['code'];
            unset($requirement['code']);

            $project->requirements()->firstOrCreate(
                ['code' => $code],
                [
                    ...$requirement,
                    'status' => 'planned',
                    'is_completed' => false,
                    'sort_order' => $sortOrder,
                ],
            );
        }
    }
}
