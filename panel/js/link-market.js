// Link Market functionality
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(element => {
        tippy(element, {
            theme: 'dark',
            placement: 'top'
        });
    });

    // Handle bulk selection
    const mainCheckbox = document.querySelector('thead input[type="checkbox"]');
    const rowCheckboxes = document.querySelectorAll('tbody input[type="checkbox"]');
    
    mainCheckbox.addEventListener('change', function() {
        rowCheckboxes.forEach(checkbox => {
            checkbox.checked = mainCheckbox.checked;
        });
        updateSelectedCount();
    });

    rowCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedCount);
    });

    // Search functionality
    const searchInput = document.querySelector('input[name="search"]');
    let searchTimeout;

    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            document.getElementById('filters-form').submit();
        }, 500);
    });
});

function updateSelectedCount() {
    const selectedCount = document.querySelectorAll('tbody input[type="checkbox"]:checked').length;
    document.getElementById('selected-count').textContent = selectedCount;
    
    // Update bulk action buttons state
    const bulkButtons = document.querySelectorAll('.bulk-action');
    bulkButtons.forEach(button => {
        button.disabled = selectedCount === 0;
    });
}

function getSelectedLinks() {
    const checkboxes = document.querySelectorAll('tbody input[type="checkbox"]:checked');
    return Array.from(checkboxes).map(cb => cb.value);
}

// Bulk actions
async function bulkAdd() {
    const textarea = document.createElement('textarea');
    textarea.className = 'w-full h-40 p-2 bg-gray-700 text-white rounded';
    textarea.placeholder = 'Her satıra bir link gelecek şekilde domainleri girin...';
    
    const result = await showModal('Toplu Link Ekleme', textarea);
    if (!result) return;
    
    const domains = textarea.value.split('\n').filter(d => d.trim());
    if (domains.length === 0) return;
    
    try {
        const response = await fetch('/api/links/bulk-add', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ domains })
        });
        
        const data = await response.json();
        if (data.success) {
            showNotification('success', `${data.added} link başarıyla eklendi`);
            location.reload();
        } else {
            showNotification('error', data.error || 'Linkler eklenirken bir hata oluştu');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('error', 'Bir hata oluştu');
    }
}

async function bulkExtend() {
    const selectedLinks = getSelectedLinks();
    if (selectedLinks.length === 0) {
        showNotification('warning', 'Lütfen link seçin');
        return;
    }
    
    const days = await showPrompt('Kaç gün uzatmak istiyorsunuz?', '30');
    if (!days) return;
    
    try {
        const response = await fetch('/api/links/bulk-extend', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ 
                links:selectedLinks,
                days: parseInt(days)
            })
        });
        
        const data = await response.json();
        if (data.success) {
            showNotification('success', `${data.extended} link başarıyla uzatıldı`);
            location.reload();
        } else {
            showNotification('error', data.error || 'Linkler uzatılırken bir hata oluştu');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('error', 'Bir hata oluştu');
    }
}

async function bulkRenew() {
    const selectedLinks = getSelectedLinks();
    if (selectedLinks.length === 0) {
        showNotification('warning', 'Lütfen link seçin');
        return;
    }
    
    if (!confirm(`${selectedLinks.length} linki yenilemek istediğinizden emin misiniz?`)) return;
    
    try {
        const response = await fetch('/api/links/bulk-renew', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ links: selectedLinks })
        });
        
        const data = await response.json();
        if (data.success) {
            showNotification('success', `${data.renewed} link başarıyla yenilendi`);
            location.reload();
        } else {
            showNotification('error', data.error || 'Linkler yenilenirken bir hata oluştu');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('error', 'Bir hata oluştu');
    }
}

async function bulkRefund() {
    const selectedLinks = getSelectedLinks();
    if (selectedLinks.length === 0) {
        showNotification('warning', 'Lütfen link seçin');
        return;
    }
    
    if (!confirm(`${selectedLinks.length} link için iade işlemi başlatmak istediğinizden emin misiniz?`)) return;
    
    try {
        const response = await fetch('/api/links/bulk-refund', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ links: selectedLinks })
        });
        
        const data = await response.json();
        if (data.success) {
            showNotification('success', `${data.refunded} link için iade işlemi başlatıldı`);
            location.reload();
        } else {
            showNotification('error', data.error || 'İade işlemi başlatılırken bir hata oluştu');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('error', 'Bir hata oluştu');
    }
}

async function bulkEdit() {
    const selectedLinks = getSelectedLinks();
    if (selectedLinks.length === 0) {
        showNotification('warning', 'Lütfen link seçin');
        return;
    }
    
    const form = document.createElement('form');
    form.innerHTML = `
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-400">Fiyat</label>
                <input type="number" name="price" class="form-input bg-gray-700 mt-1">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-400">Tür</label>
                <select name="type" class="form-select bg-gray-700 mt-1">
                    <option value="">Seçiniz</option>
                    <option value="PHP">PHP</option>
                    <option value="JS">JS</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-400">Durum</label>
                <select name="status" class="form-select bg-gray-700 mt-1">
                    <option value="">Seçiniz</option>
                    <option value="active">Aktif</option>
                    <option value="pending">Beklemede</option>
                    <option value="expired">Süresi Dolmuş</option>
                </select>
            </div>
        </div>
    `;
    
    const result = await showModal('Toplu Düzenleme', form);
    if (!result) return;
    
    const formData = new FormData(form);
    const updates = {};
    
    formData.forEach((value, key) => {
        if (value) updates[key] = value;
    });
    
    if (Object.keys(updates).length === 0) {
        showNotification('warning', 'Lütfen en az bir alan doldurun');
        return;
    }
    
    try {
        const response = await fetch('/api/links/bulk-edit', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                links: selectedLinks,
                updates
            })
        });
        
        const data = await response.json();
        if (data.success) {
            showNotification('success', `${data.updated} link başarıyla güncellendi`);
            location.reload();
        } else {
            showNotification('error', data.error || 'Linkler güncellenirken bir hata oluştu');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('error', 'Bir hata oluştu');
    }
}

// Helper functions
async function showModal(title, content) {
    return new Promise(resolve => {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4';
        modal.innerHTML = `
            <div class="bg-gray-800 rounded-lg shadow-xl max-w-lg w-full">
                <div class="px-6 py-4 border-b border-gray-700">
                    <h3 class="text-lg font-semibold">${title}</h3>
                </div>
                <div class="p-6">
                    ${content.outerHTML || content}
                </div>
                <div class="px-6 py-4 border-t border-gray-700 flex justify-end space-x-3">
                    <button class="btn-secondary" onclick="this.closest('.fixed').remove(); resolve(false)">
                        İptal
                    </button>
                    <button class="btn-primary" onclick="this.closest('.fixed').remove(); resolve(true)">
                        Tamam
                    </button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    });
}

async function showPrompt(message, defaultValue = '') {
    const input = document.createElement('input');
    input.type = 'text';
    input.className = 'form-input bg-gray-700 text-white w-full';
    input.value = defaultValue;
    
    const result = await showModal(message, input);
    return result ? input.value : null;
}

function showNotification(type, message) {
    const notification = document.createElement('div');
    notification.className = `fixed bottom-4 right-4 px-6 py-3 rounded-lg shadow-lg ${
        type === 'success' ? 'bg-green-500' :
        type === 'error' ? 'bg-red-500' :
        'bg-yellow-500'
    }`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 3000);
}

// Link actions
async function viewDetails(linkId) {
    try {
        const response = await fetch(`/api/links/${linkId}`);
        const data = await response.json();
        
        if (data.success) {
            const content = document.createElement('div');
            content.className = 'space-y-4';
            content.innerHTML = `
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-400">Domain</label>
                        <p class="mt-1">${data.link.domain}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-400">DA/PA</label>
                        <p class="mt-1">${data.link.da}/${data.link.pa}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-400">Aktif Backlink</label>
                        <p class="mt-1">${data.link.active_backlinks}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-400">Ortalama Yüklenme Süresi</label>
                        <p class="mt-1">${data.link.avg_loading_time.toFixed(2)}s</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-400">Kalite Skoru</label>
                        <p class="mt-1">${calculateQualityScore(data.link)}%</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-400">Son Kontrol</label>
                        <p class="mt-1">${new Date(data.link.last_checked).toLocaleString()}</p>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-400">Backlink Geçmişi</label>
                    <div class="mt-2 h-40 overflow-y-auto">
                        <table class="min-w-full divide-y divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left">Tarih</th>
                                    <th class="px-4 py-2 text-left">Durum</th>
                                    <th class="px-4 py-2 text-left">Yüklenme Süresi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-700">
                                ${data.history.map(h => `
                                    <tr>
                                        <td class="px-4 py-2">${new Date(h.checked_at).toLocaleString()}</td>
                                        <td class="px-4 py-2">${h.status_code === 200 ? 'Aktif' : 'Pasif'}</td>
                                        <td class="px-4 py-2">${h.loading_time.toFixed(2)}s</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
            
            await showModal('Link Detayları', content);
        } else {
            showNotification('error', data.error || 'Link detayları alınamadı');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('error', 'Bir hata oluştu');
    }
}

async function toggleFavorite(linkId) {
    try {
        const response = await fetch('/api/links/favorite', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ link_id: linkId })
        });
        
        const data = await response.json();
        if (data.success) {
            location.reload();
        } else {
            showNotification('error', data.error || 'İşlem başarısız');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('error', 'Bir hata oluştu');
    }
}

async function purchaseLink(linkId) {
    try {
        const response = await fetch(`/api/links/${linkId}`);
        const data = await response.json();
        
        if (data.success) {
            const content = document.createElement('div');
            content.className = 'space-y-4';
            content.innerHTML = `
                <div class="bg-gray-700 p-4 rounded">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-lg font-semibold">${data.link.domain}</p>
                            <p class="text-sm text-gray-400">DA: ${data.link.da} / PA: ${data.link.pa}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-bold">${data.link.price} ₺</p>
                            <p class="text-sm text-gray-400">1 Kredi</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-700 p-4 rounded">
                    <h4 class="font-semibold mb-2">Ödeme Yöntemi</h4>
                    <div class="space-y-2">
                        <label class="flex items-center space-x-2">
                            <input type="radio" name="payment" value="credits" checked>
                            <span>Kredi (${data.user_credits} krediniz var)</span>
                        </label>
                        <label class="flex items-center space-x-2">
                            <input type="radio" name="payment" value="balance">
                            <span>Bakiye (${data.user_balance} ₺)</span>
                        </label>
                    </div>
                </div>
            `;
            
            const result = await showModal('Link Satın Al', content);
            if (!result) return;
            
            const paymentMethod = content.querySelector('input[name="payment"]:checked').value;
            
            const purchaseResponse = await fetch('/api/links/purchase', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    link_id: linkId,
                    payment_method: paymentMethod
                })
            });
            
            const purchaseData = await purchaseResponse.json();
            if (purchaseData.success) {
                showNotification('success', 'Link başarıyla satın alındı');
                location.reload();
            } else {
                showNotification('error', purchaseData.error || 'Satın alma işlemi başarısız');
            }
        } else {
            showNotification('error', data.error || 'Link bilgileri alınamadı');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('error', 'Bir hata oluştu');
    }
}