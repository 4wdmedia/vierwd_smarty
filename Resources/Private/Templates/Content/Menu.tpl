{extends file='Layouts/HeaderContentFooter.tpl'}
{block name=content}
	{typoscript data=$data}
	10 = HMENU
	10 {
		special = directory
		special.value.field = pages

		1 = TMENU
		1 {
			NO {
				stdWrap.htmlSpecialChars = 1
				wrapItemAndSub = <li>|</li>
				ATagTitle.field = description // title
			}
		}
	}
	{/typoscript}
{/block}