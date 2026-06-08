@extends('layouts.admin')

@section('title', 'Gestion de Caisse')

@section('content')
<div x-data="cashSessionManager()" class="cash-management">
    {{-- En-tête --}}
    <div class="cash-header">
        <h1>💰 Gestion de Caisse Physique</h1>
        <div class="header-actions">
            <template x-if="!openSession">
                <button @click="showOpenModal = true" class="btn btn-primary btn-lg">
                    ➕ Ouvrir Session
                </button>
            </template>
            <template x-if="openSession">
                <div class="session-info">
                    <strong>Session ouverte par:</strong> <span x-text="openSession.user.name"></span><br>
                    <strong>Depuis:</strong> <span x-text="formatTime(openSession.opened_at)"></span><br>
                    <strong>Fonds initial:</strong> <span x-text="formatCurrency(openSession.initial_balance)"></span>
                </div>
                <button @click="showCloseModal = true" class="btn btn-danger">
                    ⛔ Fermer Session
                </button>
            </template>
        </div>
    </div>

    {{-- Sessions fermées du jour --}}
    <div class="closed-sessions">
        <h3>📋 Sessions fermées (aujourd'hui)</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Utilisateur</th>
                    <th>Fonds initial</th>
                    <th>Total théorique</th>
                    <th>Total réel</th>
                    <th>Écart</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="session in closedSessions" :key="session.id">
                    <tr :class="[session.difference !== 0 ? 'row-warning' : '']">
                        <td x-text="session.user.name"></td>
                        <td x-text="formatCurrency(session.initial_balance)"></td>
                        <td x-text="formatCurrency(session.theoretical_total)"></td>
                        <td x-text="formatCurrency(session.actual_total)"></td>
                        <td :class="[session.difference > 0 ? 'text-success' : 'text-danger']">
                            <strong x-text="formatCurrency(session.difference)"></strong>
                        </td>
                        <td><span class="badge badge-success" x-text="session.status"></span></td>
                        <td>
                            <button @click="viewSession(session)" class="btn-tiny">👁️</button>
                            <button @click="exportSession(session)" class="btn-tiny">📥</button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    {{-- Session ouverte - Transactions en temps réel --}}
    <template x-if="openSession">
        <div class="open-session-detail">
            <h3>💳 Enregistrement Transactions</h3>
            
            <div class="transaction-form">
                <div class="form-group">
                    <label>Montant (€)</label>
                    <input type="number" x-model.number="newTransaction.amount" step="0.01" min="0" 
                        class="form-control form-control-lg" placeholder="0.00">
                </div>

                <div class="form-group">
                    <label>Méthode</label>
                    <select x-model="newTransaction.method" class="form-control">
                        <option value="cash">💵 Espèces</option>
                        <option value="card">💳 Carte</option>
                        <option value="check">✍️ Chèque</option>
                        <option value="bank_transfer">🏦 Virement</option>
                        <option value="insurance">🏥 Assurance</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Patient (optionnel)</label>
                    <input type="text" x-model="newTransaction.patient_name" placeholder="Nom patient..."
                        class="form-control">
                </div>

                <div class="form-group">
                    <label>Référence (optionnel)</label>
                    <input type="text" x-model="newTransaction.reference" placeholder="N° chèque, N° facture..."
                        class="form-control">
                </div>

                <button @click="recordTransaction()" class="btn btn-success btn-block">
                    ✅ Enregistrer Transaction
                </button>
            </div>

            {{-- Transactions enregistrées --}}
            <div class="transactions-list">
                <h4>Transactions de la session</h4>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Heure</th>
                            <th>Montant</th>
                            <th>Méthode</th>
                            <th>Patient</th>
                            <th>Référence</th>
                            <th>Enregistré par</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="tx in transactions" :key="tx.id">
                            <tr>
                                <td><code x-text="tx.recorded_at.substring(11, 16)"></code></td>
                                <td><strong x-text="formatCurrency(tx.amount)"></strong></td>
                                <td x-text="getMethodLabel(tx.method)"></td>
                                <td x-text="tx.patient?.full_name || '-'"></td>
                                <td x-text="tx.reference || '-'"></td>
                                <td x-text="tx.recorded_by.name"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>

                <div class="summary">
                    <div class="summary-row">
                        <strong>Fonds initial:</strong>
                        <span x-text="formatCurrency(openSession.initial_balance)"></span>
                    </div>
                    <div class="summary-row">
                        <strong>Total transactions:</strong>
                        <span x-text="formatCurrency(transactionTotal)"></span>
                    </div>
                    <div class="summary-row highlight">
                        <strong>Total théorique:</strong>
                        <span x-text="formatCurrency(theoreticalTotal)"></span>
                    </div>
                </div>
            </div>
        </div>
    </template>

    {{-- Modal ouverture session --}}
    <div x-show="showOpenModal" class="modal-overlay" @click.self="showOpenModal = false">
        <div class="modal-content">
            <div class="modal-header">
                <h3>➕ Ouvrir Nouvelle Session</h3>
                <button @click="showOpenModal = false" class="btn-close">✕</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Fonds initial (€)</label>
                    <input type="number" x-model.number="openSessionData.initial_balance" step="0.01" min="0"
                        class="form-control form-control-lg" placeholder="0.00">
                    <small class="form-text text-muted">Montant en caisse à l'ouverture</small>
                </div>
            </div>
            <div class="modal-footer">
                <button @click="showOpenModal = false" class="btn btn-secondary">Annuler</button>
                <button @click="openSession()" class="btn btn-primary">Ouvrir Session</button>
            </div>
        </div>
    </div>

    {{-- Modal fermeture session --}}
    <div x-show="showCloseModal" class="modal-overlay" @click.self="showCloseModal = false">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h3>⛔ Fermer Session - Réconciliation</h3>
                <button @click="showCloseModal = false" class="btn-close">✕</button>
            </div>
            <div class="modal-body">
                <div class="reconciliation-info">
                    <div class="info-box">
                        <small>Fonds initial</small>
                        <strong x-text="formatCurrency(openSession?.initial_balance)"></strong>
                    </div>
                    <div class="info-box">
                        <small>Total théorique</small>
                        <strong x-text="formatCurrency(theoreticalTotal)" style="color: #3b82f6;"></strong>
                    </div>
                    <div class="info-box">
                        <small>Total réel (à remplir)</small>
                        <strong style="color: #10b981;">↓</strong>
                    </div>
                </div>

                <div class="form-group">
                    <label>Total Réel Compté (€)</label>
                    <input type="number" x-model.number="closeSessionData.actual_total" step="0.01" min="0"
                        class="form-control form-control-xl" 
                        placeholder="Montant total en caisse"
                        @input="calculateDifference()">
                    <small class="form-text text-muted">Comptez la caisse et entrez le total</small>
                </div>

                <div class="difference-display" x-show="closeSessionData.actual_total > 0">
                    <h4>Réconciliation</h4>
                    <div :class="['difference-box', closeSessionData.actual_total > theoreticalTotal ? 'positive' : 'negative']">
                        <div>Théorique: <strong x-text="formatCurrency(theoreticalTotal)"></strong></div>
                        <div>Réel: <strong x-text="formatCurrency(closeSessionData.actual_total)"></strong></div>
                        <div style="font-size: 1.3rem; margin-top: 1rem;">
                            Écart: 
                            <strong x-text="formatCurrency(closeSessionData.actual_total - theoreticalTotal)"
                                :style="{ color: (closeSessionData.actual_total - theoreticalTotal) > 0 ? '#10b981' : '#ef4444' }">
                            </strong>
                        </div>
                    </div>
                </div>

                <div class="form-group" x-show="closeSessionData.actual_total > 0">
                    <label>Motif écart (si applicable)</label>
                    <textarea x-model="closeSessionData.variance_reason" placeholder="Ex: Pièce retrouvée, Erreur comptage..."
                        class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button @click="showCloseModal = false" class="btn btn-secondary">Annuler</button>
                <button @click="closeSession()" class="btn btn-danger">Fermer Session</button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.cash-management {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.cash-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding: 2rem;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    border-radius: 8px;
}

.header-actions {
    display: flex;
    gap: 2rem;
    align-items: center;
}

.session-info {
    background: rgba(255,255,255,0.1);
    padding: 1rem;
    border-radius: 6px;
    font-size: 0.9rem;
    line-height: 1.6;
}

.transaction-form {
    background: #f9fafb;
    padding: 2rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.summary {
    background: #e8f5e9;
    padding: 1.5rem;
    border-radius: 6px;
    margin-top: 2rem;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    font-size: 1.1rem;
}

.summary-row.highlight {
    border-top: 2px solid #4caf50;
    padding-top: 1rem;
    margin-top: 1rem;
    font-weight: bold;
    font-size: 1.3rem;
}

.reconciliation-info {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-bottom: 2rem;
}

.info-box {
    background: #f0f9ff;
    padding: 1.5rem;
    border-radius: 6px;
    text-align: center;
    border: 2px solid #e0f2fe;
}

.info-box small {
    display: block;
    color: #64748b;
    margin-bottom: 0.5rem;
}

.info-box strong {
    font-size: 1.4rem;
    color: #0369a1;
}

.difference-box {
    background: #f3f4f6;
    padding: 1.5rem;
    border-radius: 6px;
    border: 2px solid;
    margin-bottom: 1.5rem;
}

.difference-box.positive {
    background: #ecfdf5;
    border-color: #10b981;
}

.difference-box.negative {
    background: #fef2f2;
    border-color: #ef4444;
}

.btn-block {
    width: 100%;
}

.form-control-lg {
    font-size: 1.2rem;
    padding: 0.75rem 1rem;
}

.form-control-xl {
    font-size: 1.5rem;
    padding: 1rem;
}

.row-warning {
    background: #fffbeb;
}

.table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1rem;
}

.table th {
    background: #f9fafb;
    padding: 1rem;
    text-align: left;
    border-bottom: 2px solid #e5e7eb;
}

.table td {
    padding: 1rem;
    border-bottom: 1px solid #e5e7eb;
}

.table tbody tr:hover {
    background: #f9fafb;
}
</style>
@endpush

@push('scripts')
<script>
function cashSessionManager() {
    return {
        openSession: @json($open_session ?? null),
        closedSessions: @json($closed_sessions ?? []),
        transactions: @json($transactions ?? []),
        
        showOpenModal: false,
        showCloseModal: false,
        
        openSessionData: {
            initial_balance: 0,
        },
        
        closeSessionData: {
            actual_total: 0,
            variance_reason: '',
        },
        
        newTransaction: {
            amount: 0,
            method: 'cash',
            patient_name: '',
            reference: '',
        },

        get transactionTotal() {
            return this.transactions.reduce((sum, t) => sum + t.amount, 0);
        },

        get theoreticalTotal() {
            return (this.openSession?.initial_balance || 0) + this.transactionTotal;
        },

        formatCurrency(value) {
            return new Intl.NumberFormat('fr-FR', {
                style: 'currency',
                currency: 'EUR',
            }).format(value);
        },

        formatTime(datetime) {
            return new Date(datetime).toLocaleString('fr-FR');
        },

        getMethodLabel(method) {
            const labels = {
                'cash': '💵 Espèces',
                'card': '💳 Carte',
                'check': '✍️ Chèque',
                'bank_transfer': '🏦 Virement',
                'insurance': '🏥 Assurance',
            };
            return labels[method] || method;
        },

        async openSession() {
            try {
                const response = await fetch('/secretary/cash/open', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        initial_balance: this.openSessionData.initial_balance,
                    }),
                });

                if (response.ok) {
                    const data = await response.json();
                    this.openSession = data.session;
                    this.showOpenModal = false;
                    alert('✅ Session ouverte');
                    location.reload();
                } else {
                    alert('Erreur: ' + (await response.text()));
                }
            } catch (e) {
                alert('Erreur: ' + e.message);
            }
        },

        async recordTransaction() {
            try {
                const response = await fetch(`/secretary/cash/session/${this.openSession.id}/transaction`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(this.newTransaction),
                });

                if (response.ok) {
                    const data = await response.json();
                    this.transactions.push(data.transaction);
                    this.newTransaction = { amount: 0, method: 'cash', patient_name: '', reference: '' };
                    alert('✅ Transaction enregistrée');
                } else {
                    alert('Erreur: ' + (await response.text()));
                }
            } catch (e) {
                alert('Erreur: ' + e.message);
            }
        },

        calculateDifference() {
            // Affichage automatique
        },

        async closeSession() {
            if (this.closeSessionData.actual_total === 0) {
                alert('⚠️ Veuillez entrer le montant réel');
                return;
            }

            try {
                const response = await fetch(`/secretary/cash/session/${this.openSession.id}/close`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(this.closeSessionData),
                });

                if (response.ok) {
                    alert('✅ Session fermée');
                    location.reload();
                } else {
                    alert('Erreur: ' + (await response.text()));
                }
            } catch (e) {
                alert('Erreur: ' + e.message);
            }
        },

        exportSession(session) {
            window.location.href = `/secretary/cash/session/${session.id}/export?format=csv`;
        },

        viewSession(session) {
            window.location.href = `/secretary/cash/session/${session.id}`;
        },
    };
}
</script>
@endpush
@endsection
