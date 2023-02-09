import { invoke } from '@hybridly/utils'
import type { NavigationResponse } from 'hybridly'
import { route, router } from 'hybridly'
import { computed, reactive, ref } from 'vue'
import { usePaginator } from './paginator'

declare global {
	interface Table<T extends object = never> {
		id: string
		keyName: string
		scope?: string
		columns: Column<T>[]
		filters: Filter[]
		inlineActions: InlineAction[]
		bulkActions: BulkAction[]
		records: UnwrappedPaginator<T>
		currentSorts: Record<string, Sort>
		currentFilters: Record<string, {
			is_filter_active?: boolean
			[key: string]: any
		}>
	}
}

export type RecordIdentifier = string | number

export type SortDirection = 'asc' | 'desc'

export interface Sort {
	sort: string
	column: string
	next?: string
	direction: SortDirection
}

export interface Column<T extends object = never> {
	name: keyof T
	label: string
	hidden: boolean
	sortable: boolean
	type: string
	metadata: any
}

export interface Action {
	name: string
	label: string
	type: string
	metadata: any
}

export interface Filter {
	name: string
	label: string
	type: string
	metadata: any
}

export interface BulkAction extends Action {
	/** Should deselect all records after action. */
	deselect: boolean
}

export interface InlineAction extends Action {
}

export type RecordCollection<RecordType> = Array<{
	record: RecordType
	execute: (name: string) => Promise<NavigationResponse>
	actions: InlineAction[]
	select: () => void
	deselect: () => void
	selected: boolean
	toggle: (force?: boolean) => void
}>

export type ColumnCollection<RecordType extends object = never> = Array<Column<RecordType> & {
	toggleSort: () => void
	sort: (direction: SortDirection) => void
	isSorting: (direction?: SortDirection) => boolean
}>

export type FilterCollection = Array<Filter & {
	value?: any
	apply: (value: any) => Promise<NavigationResponse | undefined>
}>

interface BulkSelection {
	/** Whether all records are selected. */
	all: boolean
	/** Included records. */
	only: Set<RecordIdentifier>
	/** Excluded records. */
	except: Set<RecordIdentifier>
}

interface BulkActionOptions {
	/** Force deselecting all records after action. */
	deselect?: boolean
}

function useBulkSelect() {
	const selection = ref<BulkSelection>({
		all: false,
		only: new Set(),
		except: new Set(),
	})

	/**
	 * Selects all records.
	 */
	function selectAll() {
		selection.value.all = true
		selection.value.only.clear()
		selection.value.except.clear()
	}

	/**
	 * Deselects all records.
	 */
	function deselectAll() {
		selection.value.all = false
		selection.value.only.clear()
		selection.value.except.clear()
	}

	/**
	 * Selects the given records.
	 */
	function select(...records: RecordIdentifier[]) {
		records.forEach((record) => selection.value.except.delete(record))
		records.forEach((record) => selection.value.only.add(record))
	}

	/**
	 * Deselects the given records.
	 */
	function deselect(...records: RecordIdentifier[]) {
		records.forEach((record) => selection.value.except.add(record))
		records.forEach((record) => selection.value.only.delete(record))
	}

	/**
	 * Toggles selection for the given records.
	 */
	function toggle(record: RecordIdentifier, force?: boolean) {
		if (selected(record) || force === false) {
			return deselect(record)
		}

		if (!selected(record) || force === true) {
			return select(record)
		}
	}

	/**
	 * Checks whether the given record is selected.
	 */
	function selected(record: RecordIdentifier) {
		if (selection.value.all) {
			return !selection.value.except.has(record)
		}

		return selection.value.only.has(record)
	}

	/**
	 * Checks whether all records are selected.
	 */
	const allSelected = computed(() => {
		return selection.value.all && selection.value.except.size === 0
	})

	return {
		allSelected,
		selectAll,
		deselectAll,
		select,
		deselect,
		toggle,
		selected,
		selection,
	}
}

/**
 * Provides convenient utils for dealing with tables.
 */
export function useTable<
	RecordType extends(Props[PropsKey] extends Table<infer RecordType> ? RecordType : never),
	TableType extends(Props[PropsKey] extends Table<RecordType> ? Table<RecordType> : never),
	Props extends object,
	PropsKey extends keyof Props,
	ColumnType extends keyof RecordType
>(props: Props, key: PropsKey): UseTableResult<RecordType, ColumnType> {
	const table = computed(() => props[key] as TableType)
	const bulk = useBulkSelect()
	const sortsKey = computed(() => table.value.scope ? `${table.value.scope}-sorts` : 'sorts')
	const filtersKey = computed(() => table.value.scope ? `${table.value.scope}-filters` : 'filters')
	const pageKey = computed(() => table.value.scope ? `${table.value.scope}-page` : 'page')

	function getKey(record: RecordType): string | number {
		return Reflect.get(record, table.value.keyName) as any
	}

	/**
	 * Resets pagination, filters and sorts.
	 */
	async function reset() {
		return await router.reload({
			data: {
				[sortsKey.value]: undefined,
				[filtersKey.value]: undefined,
				[pageKey.value]: undefined,
			},
		})
	}

	/**
	 * Resets all filters.
	 */
	async function resetFilters() {
		return await router.reload({
			data: {
				[filtersKey.value]: undefined,
			},
		})
	}

	/**
	 * Applies the given filter.
	 */
	async function applyFilter(filter: string, value: any) {
		if (!table.value.filters.find(({ name }) => name === filter)) {
			return
		}

		return await router.reload({
			data: {
				[filtersKey.value]: {
					[filter]: value === '' ? undefined : value,
				},
			},
		})
	}

	/**
	 * Resets all sorts.
	 */
	async function resetSorts() {
		return await router.reload({
			data: {
				[sortsKey.value]: undefined,
			},
		})
	}

	/**
	 * Toggles the sorting for the given column.
	 */
	async function toggleSort(column: ColumnType, direction?: SortDirection) {
		if (!table.value.columns.find(({ name, sortable }) => sortable && name === column)) {
			return
		}

		const currentSort = Reflect.get(table.value.currentSorts, column) as Sort
		const sort = invoke<string | undefined>(() => {
			if (direction) {
				return direction === 'desc' ? currentSort.column : `-${currentSort.column}`
			}

			if (currentSort?.column === column) {
				return currentSort.next || undefined
			}

			return String(column)
		})

		return await router.reload({
			data: {
				[sortsKey.value]: sort,
			},
		})
	}

	/**
	 * Applies the given sort.
	 */
	async function sort(column: ColumnType, direction: SortDirection) {
		return await router.reload({
			data: {
				[sortsKey.value]: direction === 'desc' ? `-${String(column)}` : String(column),
			},
		})
	}

	/**
	 * Determines whether the given column is being sorted.
	 */
	function isSorting(column: ColumnType, direction?: SortDirection) {
		const sort = Reflect.get(table.value.currentSorts, column)

		if (!sort) {
			return false
		}

		if (sort.column === column && !direction) {
			return true
		}

		return sort.column === column && sort.direction === direction
	}

	/**
	 * Executes the given inline action by name.
	 */
	async function executeInlineAction(action: string, record: RecordIdentifier) {
		return await router.navigate({
			method: 'post',
			url: route('hybridly.endpoint'),
			preserveState: true,
			data: {
				type: 'action:inline',
				action,
				id: table.value.id,
				record,
			},
		})
	}

	/**
	 * Executes the given bulk action for the given records.
	 */
	async function executeBulkAction(action: string, selection: BulkSelection, options?: BulkActionOptions) {
		return await router.navigate({
			method: 'post',
			url: route('hybridly.endpoint'),
			preserveState: true,
			data: {
				type: 'action:bulk',
				action,
				id: table.value.id,
				all: selection.all,
				only: [...selection.only],
				except: [...selection.except],
			},
			hooks: {
				after: () => {
					if (options?.deselect === true || table.value.bulkActions.find(({ name }) => name === action)?.deselect !== false) {
						bulk.deselectAll()
					}
				},
			},
		})
	}

	return reactive({
		reset,
		resetFilters,
		resetSorts,
		applyFilter,
		sort,
		toggleSort,
		isSorting,
		executeInlineAction,
		executeBulkAction: (action: string, options?: BulkActionOptions) => executeBulkAction(action, bulk.selection.value, options),
		selectAll: bulk.selectAll,
		deselectAll: bulk.deselectAll,
		isSelected: bulk.selected,
		allSelected: bulk.allSelected,
		selection: bulk.selection,
		currentSorts: computed(() => table.value.currentSorts),
		bulkActions: computed(() => table.value.bulkActions),
		columns: computed(() => table.value.columns.map((column) => ({
			...column,
			toggleSort: () => toggleSort(column.name as ColumnType),
			sort: (direction: SortDirection) => sort(column.name as ColumnType, direction),
			isSorting: (direction?: SortDirection) => isSorting(column.name as ColumnType, direction),
		}))),
		filters: computed(() => table.value.filters.map((filter) => ({
			...filter,
			value: table.value.currentFilters[filter.name] ?? undefined,
			apply: (value: any) => applyFilter(filter.name, value),
		}))),
		records: computed(() => table.value.records.data.map((record) => ({
			record,
			execute: (name: string) => executeInlineAction(name, getKey(record)),
			actions: table.value.inlineActions,
			select: () => bulk.select(getKey(record)),
			deselect: () => bulk.deselect(getKey(record)),
			toggle: (force?: boolean) => bulk.toggle(getKey(record), force),
			selected: bulk.selected(getKey(record)),
		}))),
		paginator: computed(() => usePaginator(table.value.records)),
	})
}

interface UseTableResult<RecordType extends object, ColumnType extends keyof RecordType = keyof RecordType> {
	reset: () => Promise<NavigationResponse>
	resetFilters: () => Promise<NavigationResponse>
	resetSorts: () => Promise<NavigationResponse>
	applyFilter: (filter: string, value: any) => Promise<NavigationResponse | undefined>
	sort: (column: ColumnType, direction: SortDirection) => Promise<NavigationResponse>
	toggleSort: (column: ColumnType) => Promise<NavigationResponse | undefined>
	isSorting: (column: ColumnType, direction?: SortDirection) => boolean
	executeInlineAction: (action: string, record: RecordIdentifier) => Promise<NavigationResponse>
	executeBulkAction: (action: string, options?: BulkActionOptions) => Promise<NavigationResponse>
	currentSorts?: Record<string, Sort>
	columns: ColumnCollection<RecordType>
	records: RecordCollection<RecordType>
	filters: FilterCollection
	bulkActions: BulkAction[]
	selectAll: () => void
	deselectAll: () => void
	isSelected: (record: RecordIdentifier) => boolean
	allSelected: boolean
	selection: BulkSelection
	paginator: ReturnType<typeof usePaginator>
}
