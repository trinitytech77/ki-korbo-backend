<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

class ServiceEngineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Passport & Travel Documents
        $travel = ServiceCategory::firstOrCreate(
            ['slug' => 'passport-travel-documents'],
            [
                'name' => 'Passport & Travel Documents',
                'icon' => 'passport',
                'description' => 'Assistance with Bangladesh e-passports, renewal, corrections, and delivery tracking.',
                'is_active' => true,
            ]
        );

        $passportRenewal = Service::firstOrCreate(
            ['slug' => 'passport-renewal'],
            [
                'service_category_id' => $travel->id,
                'name' => 'Passport Renewal',
                'description' => 'Fast-track e-passport renewal support. Includes document audit, online form assistance, appointment slot coordination, and collection escort.',
                'estimated_days' => '7-10 working days',
                'is_active' => true,
            ]
        );

        // Requirements
        $passportRenewal->requirements()->delete();
        $passportRenewal->requirements()->createMany([
            ['document_name' => 'National ID Card (NID)', 'is_mandatory' => true],
            ['document_name' => 'Existing Passport Copy', 'is_mandatory' => true],
            ['document_name' => 'Recent Lab-Print Photograph (35x45mm)', 'is_mandatory' => true],
            ['document_name' => 'Utility Bill or Proof of Present Address', 'is_mandatory' => false],
        ]);

        // Steps
        $passportRenewal->steps()->delete();
        $passportRenewal->steps()->createMany([
            ['step_name' => 'Document Verification & Audit', 'step_order' => 1],
            ['step_name' => 'Online Application & Challan Generation', 'step_order' => 2],
            ['step_name' => 'Biometric Appointment Coordination', 'step_order' => 3],
            ['step_name' => 'Regional Passport Office (RPO) Physical Escort', 'step_order' => 4],
            ['step_name' => 'Collection & Secure Home Delivery', 'step_order' => 5],
        ]);

        // Fees
        $passportRenewal->fees()->delete();
        $passportRenewal->fees()->createMany([
            ['fee_type' => 'GOVERNMENT', 'amount' => 4025.00],
            ['fee_type' => 'KORBO', 'amount' => 500.00],
            ['fee_type' => 'AGENT', 'amount' => 800.00],
        ]);

        // New E-Passport Service
        $newPassport = Service::firstOrCreate(
            ['slug' => 'new-e-passport-assistance'],
            [
                'service_category_id' => $travel->id,
                'name' => 'New E-Passport Assistance',
                'description' => 'First-time e-passport application processing with end-to-end guidance and physical assistance.',
                'estimated_days' => '15-21 working days',
                'is_active' => true,
            ]
        );

        $newPassport->requirements()->delete();
        $newPassport->requirements()->createMany([
            ['document_name' => 'Digital NID or 17-digit Online Birth Certificate', 'is_mandatory' => true],
            ['document_name' => 'Parents National ID Copies', 'is_mandatory' => true],
            ['document_name' => 'Academic / Professional Certificate', 'is_mandatory' => false],
        ]);

        $newPassport->steps()->delete();
        $newPassport->steps()->createMany([
            ['step_name' => 'Eligibility & Data Validation', 'step_order' => 1],
            ['step_name' => 'Online Form Submission', 'step_order' => 2],
            ['step_name' => 'Government Fee Payment via Bank / Portal', 'step_order' => 3],
            ['step_name' => 'Biometric Enrollment Support', 'step_order' => 4],
            ['step_name' => 'Police Verification Follow-up', 'step_order' => 5],
            ['step_name' => 'Passport Handover', 'step_order' => 6],
        ]);

        $newPassport->fees()->delete();
        $newPassport->fees()->createMany([
            ['fee_type' => 'GOVERNMENT', 'amount' => 4025.00],
            ['fee_type' => 'KORBO', 'amount' => 600.00],
            ['fee_type' => 'AGENT', 'amount' => 1000.00],
        ]);

        // 2. Civil & Identity Documents
        $identity = ServiceCategory::firstOrCreate(
            ['slug' => 'civil-identity-documents'],
            [
                'name' => 'Civil & Identity Documents',
                'icon' => 'id-card',
                'description' => 'Birth certificates, National ID corrections, marriage registrations and legal affidavits.',
                'is_active' => true,
            ]
        );

        $birthCert = Service::firstOrCreate(
            ['slug' => 'digital-birth-certificate-correction'],
            [
                'service_category_id' => $identity->id,
                'name' => 'Digital Birth Certificate Correction',
                'description' => 'Spelling and date corrections in official Union Parishad or City Corporation birth registries.',
                'estimated_days' => '5-7 working days',
                'is_active' => true,
            ]
        );

        $birthCert->requirements()->delete();
        $birthCert->requirements()->createMany([
            ['document_name' => 'Original Old Birth Certificate', 'is_mandatory' => true],
            ['document_name' => 'Parents NID copies', 'is_mandatory' => true],
            ['document_name' => 'SSC / Educational Certificate for spelling proof', 'is_mandatory' => true],
        ]);

        $birthCert->steps()->delete();
        $birthCert->steps()->createMany([
            ['step_name' => 'Document Inspection & Affidavit Draft', 'step_order' => 1],
            ['step_name' => 'Ward Councillor / Pourashava Liaison', 'step_order' => 2],
            ['step_name' => 'Central Server Digital Revision Filing', 'step_order' => 3],
            ['step_name' => 'Collection of Bilingual Signed Certificate', 'step_order' => 4],
        ]);

        $birthCert->fees()->delete();
        $birthCert->fees()->createMany([
            ['fee_type' => 'GOVERNMENT', 'amount' => 200.00],
            ['fee_type' => 'KORBO', 'amount' => 400.00],
            ['fee_type' => 'AGENT', 'amount' => 700.00],
        ]);

        // 3. Business & Trade Administration
        $business = ServiceCategory::firstOrCreate(
            ['slug' => 'business-trade-administration'],
            [
                'name' => 'Business & Trade Administration',
                'icon' => 'briefcase',
                'description' => 'Trade licenses, TIN, VAT registration, IRC/ERC, and municipal permits.',
                'is_active' => true,
            ]
        );

        $tradeLicense = Service::firstOrCreate(
            ['slug' => 'trade-license-issuance-renewal'],
            [
                'service_category_id' => $business->id,
                'name' => 'Trade License Issuance & Renewal',
                'description' => 'Procurement and annual renewal of City Corporation Trade Licenses for businesses.',
                'estimated_days' => '3-5 working days',
                'is_active' => true,
            ]
        );

        $tradeLicense->requirements()->delete();
        $tradeLicense->requirements()->createMany([
            ['document_name' => 'Rental Agreement or Holding Tax Receipt of Commercial Space', 'is_mandatory' => true],
            ['document_name' => 'Managing Director / Proprietor NID & Photo', 'is_mandatory' => true],
            ['document_name' => 'e-TIN Certificate', 'is_mandatory' => true],
        ]);

        $tradeLicense->steps()->delete();
        $tradeLicense->steps()->createMany([
            ['step_name' => 'Zonal Revenue Circle Mapping', 'step_order' => 1],
            ['step_name' => 'Form K & Fee Submission at Nagar Bhaban / Zone Office', 'step_order' => 2],
            ['step_name' => 'License Inspector Verification Coordination', 'step_order' => 3],
            ['step_name' => 'Official Trade License Collection', 'step_order' => 4],
        ]);

        $tradeLicense->fees()->delete();
        $tradeLicense->fees()->createMany([
            ['fee_type' => 'GOVERNMENT', 'amount' => 3500.00],
            ['fee_type' => 'KORBO', 'amount' => 1000.00],
            ['fee_type' => 'AGENT', 'amount' => 1500.00],
        ]);
    }
}
