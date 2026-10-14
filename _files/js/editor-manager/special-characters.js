/*!
 * kl/editor-manager/special-characters.js
 * License https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode
 * Copyright 2020 Lukas Wieditz
 * Rewritten for XenForo 2.3 without jQuery, following the core xfSmilie editor plugin.
 */

((window, document) =>
{
	'use strict'

	if (typeof FroalaEditor === 'undefined')
	{
		return
	}

	const COMMAND = 'specialCharacters'
	const RECENT_COOKIE = 'klem_specialcharacter_usage'
	const RECENT_EVENT = 'recent-klem-special-character:logged'

	function getRecentUsage ()
	{
		const value = XF.Cookie.get(RECENT_COOKIE)
		return value ? value.split(',').reverse() : []
	}

	function logRecentUsage (id)
	{
		id = String(id).trim()

		// roughly a single row on full width displays
		const limit = XF.Feature.has('hiddenscroll') ? 12 : 11
		const value = XF.Cookie.get(RECENT_COOKIE)
		let recent = value ? value.split(',') : []

		const existing = recent.indexOf(id)
		if (existing !== -1)
		{
			recent.splice(existing, 1)
		}

		recent.push(id)

		if (recent.length > limit)
		{
			recent = recent.slice(-limit)
		}

		XF.Cookie.set(
			RECENT_COOKIE,
			recent.join(','),
			new Date(new Date().setFullYear(new Date().getFullYear() + 1)),
		)

		XF.trigger(document, RECENT_EVENT)
	}

	FroalaEditor.PLUGINS.klSpecialCharacters = function (editor)
	{
		let initialized = false
		let loaded = false
		let menu
		let menuScroll
		let scrollTop = 0
		let flashTimeout
		let logTimeout
		let searchTimer

		function showMenu ()
		{
			selectionSave()

			XF.EditorHelpers.blur(editor)

			const btn = editor.$tb.find(`.fr-command[data-cmd="${ COMMAND }"]`)[0]
			if (!btn)
			{
				return
			}

			if (!initialized)
			{
				initialized = true

				let menuHtml = document.querySelector('.js-xfEditorMenu').innerHTML.trim()
				menuHtml = Mustache.render(menuHtml, { href: XF.canonicalizeUrl('index.php?editor/kl-em-special-chars') })

				const fragment = document.createRange().createContextualFragment(menuHtml)
				menu = fragment.querySelector('.menu')
				menu.classList.add('menu--specialChars')

				btn.insertAdjacentElement('afterend', menu)
				btn.dataset.xfClick = 'menu'

				const handler = XF.Event.getElementHandler(btn, 'menu', 'click')

				XF.on(btn, 'menu:complete', () =>
				{
					menuScroll = menu.querySelector('.menu-scroller')

					if (!loaded)
					{
						loaded = true

						menuScroll.querySelectorAll('.js-specialCharacter').forEach(el => XF.on(el, 'click', insertCharacter))

						const search = menu.querySelector('.js-specialCharacterSearch')
						XF.on(search, 'input', performSearch)

						XF.on(menu.querySelector('.js-specialCharacterCloser'), 'click', () => XF.EditorHelpers.focus(editor))

						XF.on(document, RECENT_EVENT, updateRecent)

						editor.events.on('commands.mousedown', $el =>
						{
							if ($el.data('cmd') !== COMMAND)
							{
								handler.close()
							}
						})

						XF.on(menu, 'menu:closed', () =>
						{
							scrollTop = menuScroll.scrollTop
						})
					}

					menuScroll.scroll({ top: scrollTop })
				})

				XF.on(menu, 'menu:closed', () => setTimeout(() => editor.markers.remove(), 50))
			}

			const clickHandlers = XF.DataStore.get(btn, 'xf-click-handlers')
			if (clickHandlers && clickHandlers.menu)
			{
				clickHandlers.menu.toggle()
			}
		}

		function insertCharacter (e)
		{
			const target = e.currentTarget
			const html = target.innerHTML.trim()

			XF.EditorHelpers.bypassBrowserShims(editor, () =>
			{
				selectionRestore()
				XF.EditorHelpers.focus(editor)
				editor.undo.saveStep()
				editor.html.insert(html)
				editor.undo.saveStep()
				selectionSave()
				XF.EditorHelpers.blur(editor)
				XF.EditorHelpers.normalizeAfterInsert(editor)
			})

			if (menu)
			{
				const insertRow = menu.querySelector('.js-specialCharacterInsertedRow')
				insertRow.querySelector('.js-specialCharacterInsert').innerHTML = html
				XF.Transition.addClassTransitioned(insertRow, 'is-active')

				clearTimeout(flashTimeout)
				flashTimeout = setTimeout(() => XF.Transition.removeClassTransitioned(insertRow, 'is-active'), 1500)
			}

			clearTimeout(logTimeout)
			logTimeout = setTimeout(() => logRecentUsage(target.dataset.id), 1500)
		}

		function performSearch ()
		{
			const input = this
			const fullList = menu.querySelector('.js-specialCharacterFullList')
			const searchResults = menu.querySelector('.js-specialCharacterSearchResults')

			clearTimeout(searchTimer)

			searchTimer = setTimeout(() =>
			{
				const value = input.value

				if (!value || value.length < 2)
				{
					XF.display(searchResults, 'none')
					XF.display(fullList)
					return
				}

				const url = XF.canonicalizeUrl('index.php?editor/kl-em-special-chars/search')
				XF.ajax('GET', url, { q: value }, data =>
				{
					if (!data.html)
					{
						return
					}

					XF.setupHtmlInsert(data.html, html =>
					{
						XF.display(fullList, 'none')
						searchResults.innerHTML = html.outerHTML
						XF.display(searchResults)

						searchResults.querySelectorAll('.js-specialCharacter').forEach(el => XF.on(el, 'click', insertCharacter))
					})
				})
			}, 300)
		}

		function updateRecent ()
		{
			const recent = getRecentUsage()
			const recentHeader = menuScroll.querySelector('.js-recentHeader')
			const recentBlock = menuScroll.querySelector('.js-recentBlock')
			const recentList = recentBlock.querySelector('.js-recentList')
			const lists = menuScroll.querySelectorAll('.js-specialCharacterList')

			if (!recent.length)
			{
				return
			}

			recentList.innerHTML = ''

			for (const id of recent)
			{
				for (const list of lists)
				{
					const character = list.querySelector(`.js-specialCharacter[data-id="${ CSS.escape(id) }"]`)
					if (character)
					{
						recentList.appendChild(character.closest('li').cloneNode(true))
						break
					}
				}
			}

			recentList.querySelectorAll('.js-specialCharacter').forEach(el => XF.on(el, 'click', insertCharacter))

			if (recentBlock.classList.contains('is-hidden'))
			{
				XF.display(recentBlock, 'none')
				recentBlock.classList.remove('is-hidden')
				recentHeader.classList.remove('is-hidden')
				XF.Animate.fadeDown(recentBlock, {
					speed: XF.config.speed.fast,
				})
			}
		}

		function selectionSave ()
		{
			editor.selection.save()
		}

		function selectionRestore ()
		{
			editor.selection.restore()
		}

		return {
			showMenu,
		}
	}

	FroalaEditor.DefineIcon(COMMAND, { NAME: 'omega' })
	FroalaEditor.RegisterCommand(COMMAND, {
		title: 'Special Characters',
		icon: COMMAND,
		undo: false,
		focus: false,
		refreshOnCallback: false,
		callback ()
		{
			this.klSpecialCharacters.showMenu()
		},
	})
})(window, document)
