UPDATE mia_client_leads SET phone = '' WHERE phone != '' AND lid IS NOT NULL AND phone = lid AND phone NOT LIKE '120363%';
