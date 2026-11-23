// ---------- Live search + category filter ----------
var searchBox = document.getElementById('search');
var catFilter = document.getElementById('catFilter');

function filterRows() {
  var text = searchBox.value.toLowerCase();
  var cat = catFilter.value;
  var rows = document.querySelectorAll('#productTable tbody tr');
  rows.forEach(function (row) {
    var matchText = row.dataset.search.indexOf(text) !== -1;
    var matchCat = (cat === '' || row.dataset.cat === cat);
    row.style.display = (matchText && matchCat) ? '' : 'none';
  });
}
if (searchBox) {
  searchBox.addEventListener('input', filterRows);
  catFilter.addEventListener('change', filterRows);
}

// ---------- Fill the Edit modal ----------
document.querySelectorAll('.edit-btn').forEach(function (btn) {
  btn.addEventListener('click', function () {
    document.getElementById('edit_id').value = btn.dataset.id;
    document.getElementById('edit_name').value = btn.dataset.name;
    document.getElementById('edit_sku').value = btn.dataset.sku;
    document.getElementById('edit_category').value = btn.dataset.category;
    document.getElementById('edit_price').value = btn.dataset.price;
    document.getElementById('edit_qty').value = btn.dataset.qty;
    document.getElementById('edit_min').value = btn.dataset.min;
    document.getElementById('edit_desc').value = btn.dataset.desc;
  });
});

// ---------- Fill the Delete modal ----------
document.querySelectorAll('.delete-btn').forEach(function (btn) {
  btn.addEventListener('click', function () {
    document.getElementById('delete_id').value = btn.dataset.id;
    document.getElementById('delete_name').textContent = btn.dataset.name;
  });
});