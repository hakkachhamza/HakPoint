<?php
$quotes = data_read('quotes', []);
$id = (int)($_GET['id'] ?? 0);
$quote = find_row_by_id($quotes, $id);
if(!$quote) redirect_to('index.php?page=quotes');
$new = $quote;
$new['id'] = next_id($quotes);
require_once __DIR__.'/_helpers.php';
$new['ref'] = quote_ref($new['id']);
$new['status'] = 'Brouillon';
$new['created_at'] = date('d/m/Y H:i');
$new['updated_at'] = date('d/m/Y H:i');
$new['cloned_from'] = $quote['ref'] ?? '';
$new=array_merge($new, ge_author_fields('author'));
$quotes[] = $new;
data_write('quotes', $quotes);
redirect_to('index.php?page=quote_show&id='.$new['id']);
