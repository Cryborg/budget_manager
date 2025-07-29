/**
 * Gestion de la persistance localStorage pour l'application Budget Manager
 */

class BudgetManagerStorage {
    constructor() {
        this.prefix = 'budget_manager_';
        this.init();
    }

    /**
     * Initialisation du système de persistance
     */
    init() {
        this.initChartFilters();
        this.initTablePreferences();
        this.observeChanges();
    }

    /**
     * Sauvegarde une valeur dans le localStorage
     */
    set(key, value) {
        try {
            localStorage.setItem(this.prefix + key, JSON.stringify(value));
        } catch (e) {
            console.warn('Impossible de sauvegarder dans le localStorage:', e);
        }
    }

    /**
     * Récupère une valeur depuis le localStorage
     */
    get(key, defaultValue = null) {
        try {
            const item = localStorage.getItem(this.prefix + key);
            return item ? JSON.parse(item) : defaultValue;
        } catch (e) {
            console.warn('Impossible de lire depuis le localStorage:', e);
            return defaultValue;
        }
    }

    /**
     * Supprime une valeur du localStorage
     */
    remove(key) {
        try {
            localStorage.removeItem(this.prefix + key);
        } catch (e) {
            console.warn('Impossible de supprimer du localStorage:', e);
        }
    }

    /**
     * Initialise la persistance des filtres de graphiques
     */
    initChartFilters() {
        // Restaurer les filtres des graphiques au chargement
        document.addEventListener('DOMContentLoaded', () => {
            this.restoreChartFilters();
        });

        // Observer les changements de filtres
        document.addEventListener('change', (e) => {
            if (e.target.matches('[data-chart-filter]')) {
                const chartType = e.target.getAttribute('data-chart-filter');
                const value = e.target.value;
                this.set(`chart_filter_${chartType}`, value);
            }
        });
    }

    /**
     * Restaure les filtres des graphiques depuis le localStorage
     */
    restoreChartFilters() {
        // AccountEvolutionChart
        const accountEvolutionFilter = this.get('chart_filter_account_evolution', '24');
        const accountEvolutionSelect = document.querySelector('[data-chart-filter="account_evolution"]');
        if (accountEvolutionSelect && accountEvolutionSelect.value !== accountEvolutionFilter) {
            accountEvolutionSelect.value = accountEvolutionFilter;
            this.triggerChange(accountEvolutionSelect);
        }

        // IncomeExpenseChart  
        const incomeExpenseFilter = this.get('chart_filter_income_expense', '24');
        const incomeExpenseSelect = document.querySelector('[data-chart-filter="income_expense"]');
        if (incomeExpenseSelect && incomeExpenseSelect.value !== incomeExpenseFilter) {
            incomeExpenseSelect.value = incomeExpenseFilter;
            this.triggerChange(incomeExpenseSelect);
        }
    }

    /**
     * Déclenche un événement change sur un élément
     */
    triggerChange(element) {
        if (element) {
            const event = new Event('change', { bubbles: true });
            element.dispatchEvent(event);
        }
    }

    /**
     * Initialise la persistance des préférences des tableaux
     */
    initTablePreferences() {
        // Observer les changements de tri, pagination, recherche
        document.addEventListener('click', (e) => {
            // Tri des colonnes
            if (e.target.matches('[data-sortable]') || e.target.closest('[data-sortable]')) {
                const table = e.target.closest('[data-table-name]');
                if (table) {
                    const tableName = table.getAttribute('data-table-name');
                    setTimeout(() => {
                        this.saveTableSort(tableName);
                    }, 100);
                }
            }

            // Pagination
            if (e.target.matches('[data-pagination]') || e.target.closest('[data-pagination]')) {
                const table = e.target.closest('[data-table-name]');
                if (table) {
                    const tableName = table.getAttribute('data-table-name');
                    setTimeout(() => {
                        this.saveTablePagination(tableName);
                    }, 100);
                }
            }
        });

        // Observer les changements de recherche
        document.addEventListener('input', (e) => {
            if (e.target.matches('[data-table-search]')) {
                const tableName = e.target.getAttribute('data-table-search');
                this.debounce(() => {
                    this.set(`table_search_${tableName}`, e.target.value);
                }, 500);
            }
        });

        // Restaurer les préférences au chargement
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                this.restoreTablePreferences();
            }, 1000); // Attendre que Filament soit chargé
        });
    }

    /**
     * Sauvegarde le tri d'un tableau
     */
    saveTableSort(tableName) {
        const table = document.querySelector(`[data-table-name="${tableName}"]`);
        if (!table) return;

        const sortedColumn = table.querySelector('[data-sort-direction]');
        if (sortedColumn) {
            const columnName = sortedColumn.getAttribute('data-column-name') || 
                              sortedColumn.getAttribute('wire:click')?.match(/sortBy\('([^']+)'/)?.[1];
            const direction = sortedColumn.getAttribute('data-sort-direction');
            
            if (columnName && direction) {
                this.set(`table_sort_${tableName}`, {
                    column: columnName,
                    direction: direction
                });
            }
        }
    }

    /**
     * Sauvegarde la pagination d'un tableau
     */
    saveTablePagination(tableName) {
        const table = document.querySelector(`[data-table-name="${tableName}"]`);
        if (!table) return;

        const currentPage = table.querySelector('[data-current-page]')?.getAttribute('data-current-page') ||
                           new URLSearchParams(window.location.search).get('page') || '1';
        
        const perPage = table.querySelector('[data-per-page]')?.value ||
                       new URLSearchParams(window.location.search).get('per_page') || '10';

        this.set(`table_pagination_${tableName}`, {
            page: parseInt(currentPage),
            perPage: parseInt(perPage)
        });
    }

    /**
     * Restaure les préférences des tableaux
     */
    restoreTablePreferences() {
        // Récupérer tous les tableaux avec un nom
        const tables = document.querySelectorAll('[data-table-name]');
        
        tables.forEach(table => {
            const tableName = table.getAttribute('data-table-name');
            
            // Restaurer la recherche
            const searchInput = table.querySelector(`[data-table-search="${tableName}"]`);
            const savedSearch = this.get(`table_search_${tableName}`);
            if (searchInput && savedSearch) {
                searchInput.value = savedSearch;
                this.triggerChange(searchInput);
            }

            // Restaurer le tri
            const savedSort = this.get(`table_sort_${tableName}`);
            if (savedSort) {
                const sortButton = table.querySelector(`[data-column-name="${savedSort.column}"]`) ||
                                  table.querySelector(`[wire\\:click*="sortBy('${savedSort.column}')"]`);
                if (sortButton) {
                    // Simuler le clic pour restaurer le tri
                    setTimeout(() => {
                        sortButton.click();
                        if (savedSort.direction === 'desc') {
                            setTimeout(() => sortButton.click(), 100);
                        }
                    }, 200);
                }
            }

            // Restaurer la pagination
            const savedPagination = this.get(`table_pagination_${tableName}`);
            if (savedPagination) {
                const perPageSelect = table.querySelector('[data-per-page]');
                if (perPageSelect && perPageSelect.value !== savedPagination.perPage.toString()) {
                    perPageSelect.value = savedPagination.perPage;
                    this.triggerChange(perPageSelect);
                }

                // La page sera restaurée via l'URL si nécessaire
                if (savedPagination.page > 1) {
                    const url = new URL(window.location);
                    if (!url.searchParams.get('page')) {
                        url.searchParams.set('page', savedPagination.page);
                        // Ne pas rediriger automatiquement, laisser l'utilisateur naviguer
                    }
                }
            }
        });
    }

    /**
     * Initialise l'observation des changements
     */
    observeChanges() {
        // Observer les changements dans le DOM pour les nouveaux éléments ajoutés dynamiquement
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        // Réinitialiser les filtres des graphiques si de nouveaux graphiques sont ajoutés
                        if (node.matches('[data-chart-filter]') || node.querySelector('[data-chart-filter]')) {
                            setTimeout(() => this.restoreChartFilters(), 100);
                        }

                        // Réinitialiser les tableaux si de nouveaux tableaux sont ajoutés
                        if (node.matches('[data-table-name]') || node.querySelector('[data-table-name]')) {
                            setTimeout(() => this.restoreTablePreferences(), 500);
                        }
                    }
                });
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    /**
     * Fonction utilitaire de debounce
     */
    debounce(func, wait) {
        clearTimeout(this.debounceTimeout);
        this.debounceTimeout = setTimeout(func, wait);
    }

    /**
     * Nettoie les données anciennes du localStorage (appelé périodiquement)
     */
    cleanup() {
        const now = Date.now();
        const maxAge = 30 * 24 * 60 * 60 * 1000; // 30 jours

        Object.keys(localStorage).forEach(key => {
            if (key.startsWith(this.prefix)) {
                try {
                    const data = JSON.parse(localStorage.getItem(key));
                    if (data && data.timestamp && (now - data.timestamp) > maxAge) {
                        localStorage.removeItem(key);
                    }
                } catch (e) {
                    // Si on ne peut pas parser, c'est une ancienne donnée, on la garde
                }
            }
        });
    }

    /**
     * Sauvegarde les préférences de widgets (réduit/étendu)
     */
    saveWidgetState(widgetId, isCollapsed) {
        this.set(`widget_state_${widgetId}`, {
            collapsed: isCollapsed,
            timestamp: Date.now()
        });
    }

    /**
     * Récupère l'état d'un widget
     */
    getWidgetState(widgetId) {
        const state = this.get(`widget_state_${widgetId}`, { collapsed: false });
        return state.collapsed;
    }
}

// Initialiser le système de persistance
const budgetStorage = new BudgetManagerStorage();

// Nettoyer périodiquement (une fois par semaine)
if (Math.random() < 0.01) { // 1% de chance à chaque chargement
    budgetStorage.cleanup();
}

// Exporter pour utilisation globale
window.budgetStorage = budgetStorage;