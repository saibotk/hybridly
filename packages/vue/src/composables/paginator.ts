import { computed } from 'vue'

declare global {
	/**
	 * Paginated data with metadata in a `meta` wrap.
	 */
	interface Paginator<T = any> {
		data: T[]
		meta: PaginatorMeta
		links: PaginatorLink[]
	}

	/**
	 * Paginated data without metadata wrapping.
	 */
	interface UnwrappedPaginator<T = any> extends PaginatorMeta {
		data: T[]
		links: PaginatorLink[]
	}

	interface PaginatorLink {
		url?: string
		label: string
		active: boolean
	}

	interface PaginatorMeta {
		path: string
		from: number
		to: number
		total: number
		per_page: number
		current_page: number
		last_page: number
		first_page_url: string
		last_page_url: string
		next_page_url: string | undefined
		prev_page_url: string | undefined
		links?: PaginatorLink[]
	}

	interface PaginatorItem {
		url: string | undefined
		label: string
		isPage: boolean
		isActive: boolean
		isPrevious: boolean
		isNext: boolean
		isFirst: boolean
		isLast: boolean
		isCurrent: boolean
		isSeparator: boolean
	}
}

export function usePaginator<T = any>(paginator: UnwrappedPaginator<T>) {
	const meta = computed(() => paginator as PaginatorMeta)
	const links = computed(() => paginator.links)
	const items = computed(() => links.value.map((link, index) => {
		return {
			url: link.url,
			label: link.label,
			isPage: !isNaN(+link.label),
			isFirst: index === 1,
			isPrevious: index === 0,
			isNext: index === links.value.length - 1,
			isLast: index === links.value.length - 2,
			isCurrent: link.active,
			isSeparator: link.label === '...',
			isActive: !!link.url && !link.active,
		}
	}) as PaginatorItem[])

	return {
		meta,
		links,
		items,
		pages: items.value.filter((item) => item.isPage || item.isSeparator) as PaginatorItem[],
		current: items.value.find((item) => item.isCurrent),
		previous: items.value.find((item) => item.isPrevious),
		next: items.value.find((item) => item.isNext),
		from: meta.value.from,
		to: meta.value.to,
		total: meta.value.total,
	}
}
