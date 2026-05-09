<?php
return [
    // General / Titles
    'title' => '💊 Inventory Management for Small Pharmacies',
    'welcome' => 'Welcome,',
    'logout' => 'Logout',
    'dashboard_title' => 'Welcome to Dashboard',
    'dashboard_subtitle' => 'You can access main features from here.',

    // Navbar / Menu
    'medicine_list' => 'Medicines',
    'goods_receipt' => 'Goods Receipt',
    'sales' => 'Sales',
    'reports' => 'Reports',
    'suppliers' => 'Suppliers',
    'reorder_alerts' => 'Reorder Alerts',
    'expiry_alerts' => 'Expiry Alerts',
    'nav_medicines' => 'Medicines',
'nav_goods_receipt' => 'Goods Receipt',
'nav_sales' => 'Sales',
'nav_reports' => 'Reports',
'nav_sales_report' => 'Sales Report',
'nav_low_stock_report' => 'Low Stock Report',
'nav_waste_report' => 'Waste Report',
'nav_reorder_alerts' => 'Reorder Alerts',
'nav_expiry_alerts' => 'Expiry Alerts',
'nav_suppliers' => 'Suppliers',


    // Dashboard Cards / Sections
    'goods_receipt_title' => 'Goods Receipt',
    'goods_receipt_desc' => 'Record received medicines and update stock.',
    'goods_receipt_btn' => 'Record Receipt',

    'suppliers_title' => 'Suppliers',
    'suppliers_desc' => 'Manage your medicine suppliers.',
    'suppliers_btn' => 'Manage Suppliers',

    'reorder_title' => 'Reorder Alerts',
    'reorder_desc' => 'Monitor medicines that need reordering.',
    'reorder_btn' => 'View Alerts',

    'expiry_title' => 'Expiry Alerts',
    'expiry_desc' => 'Check upcoming or expired medicines',
    'expiry_btn' => 'Expiry Alerts',

    // Medicine Management
    'manage_medicines_title' => 'Medicine Management',
    'manage_medicines_desc' => 'Add, Edit, or Delete medicines',
    'manage_medicines_btn' => 'Manage Medicines',
    'add_medicine' => 'Add Medicine',
    'medicine_list_title' => 'Medicine List',

    'brand_name' => 'Brand Name',
    'generic_name' => 'Generic Name',
    'barcode' => 'Barcode',
    'type' => 'Type',
    'dosage' => 'Dosage',
    'dosage_form' => 'Dosage Form',
    'category' => 'Category',
    'quantity' => 'Quantity',
    'unit' => 'Unit',
    'lot_number' => 'Lot Number',
    'mfg_date' => 'Manufacturing Date',
    'expiry_date' => 'Expiry Date',
    'cost_price' => 'Cost Price',
    'selling_price' => 'Selling Price',
    'min_reorder' => 'Minimum Reorder Level',
    'reorder_level' => 'Reorder Level',

    'actions' => 'Actions',
    'edit' => 'Edit',
    'delete' => 'Delete',
    'confirm_delete' => 'Are you sure you want to delete this item?',

    // Sales and Purchase
    'sales_title' => 'Sales / Purchase Records',
    'sales_desc' => 'Track sales, purchases, and stock clearing',
    'sales_btn' => 'Manage Sales',
    'select_medicine' => 'Select Medicine',
    'lot_number_select' => 'Select Lot Number',
    'selling_price_label' => 'Selling Price (Ks)',
    'quantity_label' => 'Quantity',
    'discount_percent' => 'Discount (%)',
    'total_amount' => 'Total Amount (Ks)',
    'payment_method' => 'Payment Method',
    'cash' => 'Cash',
    'kbz_pay' => 'KBZ Pay',
    'wave_money' => 'Wave Money',
    'record_sale_btn' => 'Record Sale',

    // Disposal / Wastage
    'disposal_title' => 'Record Disposal / Wastage',
    'waste_reason' => 'Reason',
    'waste_quantity' => 'Quantity (Waste)',
    'waste_cart_empty' => 'No items added',

    // Reports
    'reports_title' => 'Reports',
    'daily_sales' => 'Daily Sales',
    'weekly_sales' => 'Weekly Sales',
    'monthly_sales' => 'Monthly Sales',
    'yearly_sales' => 'Yearly Sales',
    'stock_report' => 'Stock Report',
    'expiry_report' => 'Expiry Report',
    'low_stock_report' => 'Low Stock Report',
    'waste_report' => 'Waste Report',
    'reorder_report' => 'Reorder Alerts Report',

    'export_excel' => 'Export to Excel',
    'export_pdf' => 'Export to PDF',

    'top_selling_medicines' => 'Top Selling Medicines',
    'total_revenue' => 'Total Revenue',
    'total_profit' => 'Total Profit',

    // Buttons and form actions
    'save' => 'Save',
    'cancel' => 'Cancel',
    'search' => 'Search',
    'filter' => 'Filter',
    'add_new' => 'Add New',
    'update' => 'Update',
    'close' => 'Close',
    'submit' => 'Submit',

    // Validation messages
    'required_field' => 'This field is required.',
    'invalid_input' => 'Invalid input.',
    'min_length' => 'Minimum length is :min characters.',
    'max_length' => 'Maximum length is :max characters.',

    // Misc
    'no_records_found' => 'No records found.',
    'loading' => 'Loading...',
    'confirm_action' => 'Please confirm this action.',
    'success_save' => 'Record saved successfully.',
    'success_delete' => 'Record deleted successfully.',
    'error_occurred' => 'An error occurred. Please try again.',
     'goods_receipt_title' => 'Goods Receipt',
    'supplier' => 'Supplier',
    'select_supplier' => '— Select Supplier —',
    'supplier_hint' => 'Link this receipt to a supplier for better traceability.',
    'medicine' => 'Medicine',
    'medicine_placeholder' => 'Type name or barcode…',
    'medicine_hint' => 'Choose from suggestions; the selected medicine will be linked by ID.',
    'barcode' => 'Barcode',
    'barcode_placeholder' => 'Leave blank or click Generate',
    'generate' => 'Generate',
    'barcode_hint' => 'If no barcode exists, generate one automatically.',
    'lot_number' => 'Lot Number',
    'mfg_date' => 'Manufacturing Date',
    'expiry_date' => 'Expiry Date',
    'purchase_price' => 'Purchase Price (Ks)',
    'selling_price' => 'Selling Price (Ks)',
    'selling_price_hint' => 'Set or update the current selling price.',
    'quantity' => 'Quantity',
    'save' => 'Save',
    'reset' => 'Reset',

    // Errors
    'error_select_medicine' => 'Please select a valid medicine from the list.',
    'error_expiry_past' => 'Expiry date cannot be in the past.',
    'error_mfg_after_exp' => 'Manufacturing date cannot be later than expiry date.',

    // Footer
    'footer' => 'Inventory Management for Small Pharmacies',
];
