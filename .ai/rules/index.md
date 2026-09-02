# Project Rules Index

Before planning or editing, find the row whose globs match the file's path and read that rule file.

| Applies to | Rule file |
| --- | --- |
| app/**,database/**,phpstan.neon | .ai/rules/app.md |
| app/Http/Controllers/Auth/**,.env | .ai/rules/auth.md |
| app/Filament/Clusters/Sales/**, app/Filament/Clusters/Partners/Resources/Customers/** | .ai/rules/customers.md |
| app/Http/Controllers/Auth/**,app/Models/User.php,routes/web.php,app/Providers/Filament/DashboardPanelProvider.php | .ai/rules/filament.md |
| app/Models/{Payment,PaymentAllocation}.php,app/Services/PaymentAllocator.php,app/Filament/Clusters/Finance/** | .ai/rules/finance.md |
| app/Models/{Product,PriceList,Sale,Customer}.php, app/Models/{Purchase,PurchaseLine,Supplier,SupplierProductLink}.php | .ai/rules/models.md |
| database/seeders/ShieldSeeder.php,app/Filament/**/Pages/** | .ai/rules/pages.md |
| app/Models/{PerceptionType,PurchasePerception,SupplierPerceptionLink}.php,app/Services/PerceptionLinkMemory.php,app/Filament/Clusters/Purchases/**,app/Filament/Clusters/Settings/Resources/PerceptionTypes/** | .ai/rules/perception-types.md |
| app/Filament/Clusters/Settings/Resources/**,app/Policies/**,config/filament-shield.php,config/permission.php,app/Models/*.php | .ai/rules/policies-models.md |
| app/Filament/Clusters/Purchases/**, app/Models/{Purchase,PurchaseLine,Supplier,SupplierProductLink}.php, app/Services/{InvoiceExtractor,InvoiceImagePreparer,SupplierMatcher,ProductLinkMemory}.php | .ai/rules/purchases.md |
| app/Filament/Clusters/Sales/** | .ai/rules/sales.md |
