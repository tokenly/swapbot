# ---- begin references
sbAdmin = sbAdmin or {}; sbAdmin.form = require './10_form_helpers'
sbAdmin = sbAdmin or {}; sbAdmin.utils = require './10_utils'
# ---- end references

# A repeating group in a form
groupBuilder = {}


buildGroupProp = (config)->
    emptyItem = buildNewItem(config)
    return m.prop([emptyItem])

buildNewItem = (config, defaultValues=null)->
    emptyItem = {}
    for fieldDef in config.fields
        value = ''
        if defaultValues?[fieldDef.name]
            value = defaultValues[fieldDef.name]
        emptyItem[fieldDef.name] = m.prop(value)
    return emptyItem


buildRemoveItemFn = (number, groupProp)->
    return (e)->
        e.preventDefault()

        # filter newItems
        newItems = groupProp().filter (item, index)->
            return (index != number - 1)
        groupProp(newItems)
        return

groupBuilder.newGroup = (config)->
    formGroup = {}
    idPrefix = config.id or "group"
    config.displayOnly = config.displayOnly or false
    numberOfColumns = if config.displayOnly then 12 else 11

    # ############################################################################################################
    # build a row

    newRowBuilder = (number, item)->
        rowBuilder = {}

        rowBuilder.field = (labelText, propName, placeholder_or_attributes=null, overrideColumnWidth=null)->
            prop = item[propName]
            id = "#{idPrefix}_#{propName}_#{number}"
            if typeof placeholder_or_attributes is 'object'
                attrs = placeholder_or_attributes
                attrs.id = attrs.id or id
            else
                attrs = {id: id}
                attrs.placeholder = placeholder_or_attributes if placeholder_or_attributes

            if labelText == null
                # el = m("div", {class: "form-group"}, [sbAdmin.form.mInputEl(attrs, prop)])
                el = sbAdmin.form.mInputEl(attrs, prop)
            else
                el = sbAdmin.form.mFormField(labelText, attrs, prop)

            return {colWidth: overrideColumnWidth, el: el}

        rowBuilder.value = (labelText, propName, attributes=null, overrideColumnWidth=null)->
            prop = item[propName]
            id = "#{idPrefix}_#{propName}_#{number}"
            if typeof attributes is 'object'
                attrs = attributes
                attrs.id = attrs.id or id
            else
                attrs = {id: id}

            if labelText == null
                el = m("span", {}, prop())
            else
                el = sbAdmin.form.mValueDisplay(labelText, attrs, prop())

            return {colWidth: overrideColumnWidth, el: el}

        rowBuilder.header = (headerText)->
            return m("h4", headerText)

        rowBuilder.row = (rowBuilderFieldDefs)->
            rowBuilderFieldDefsCount = rowBuilderFieldDefs.length

            # split the columns into equal lengths
            overrides = for rowBuilderFieldDef in rowBuilderFieldDefs
                rowBuilderFieldDef.colWidth
            colSizes = sbAdmin.utils.splitColumnsWithOverrides(rowBuilderFieldDefsCount, numberOfColumns, overrides)

            # build the inputs
            colEls = rowBuilderFieldDefs.map (rowBuilderFieldDef, offset)->
                return m("div", {class: "col-md-#{colSizes[offset]}"}, rowBuilderFieldDef.el)

            # add remove link
            if not config.displayOnly
                colEls.push(
                    m("div", {class: "col-md-1"}, [
                        m("a", {class: "remove-link"+(if config.useCompactNumberedLayout? then " remove-link-compact" else ""), href: '#remove', onclick: buildRemoveItemFn(number, formGroup.prop), style: if number == 1 then {display: 'none'} else ""}, [
                            m("span", {class: "glyphicon glyphicon-remove-circle", title: "Remove Item #{number}"}, ''),
                        ]),
                    ]),
                )
            return m("div", {class: "item-group"+(if config.useCompactNumberedLayout? then " form-group" else "")}, [
                m("div", { class: "row"}, colEls),
            ])

        return rowBuilder

    # ############################################################################################################
    # formGroup api

    formGroup.prop = buildGroupProp(config)

    formGroup.buildInputs = ()->
        if config.buildAllItemRows?
            return config.buildAllItemRows(formGroup.prop())

        inputs = formGroup.prop().map (item, offset)->
            number = offset + 1

            row = config.buildItemRow(newRowBuilder(number, item), number, item)

            return row
            
        # add asset
        inputs.push(m("div", {class: "form-group"}, [
                m("a", {class: "", href: '#add', onclick: formGroup.addItem}, [
                    m("span", {class: "glyphicon glyphicon-plus"}, ''),
                    m("span", {}, " "+(config.addLabel or "Add Another Item")),
                ]),
        ]))
        return inputs

    formGroup.buildValues = ()->
        if config.buildAllItemRows?
            return config.buildAllItemRows(formGroup.prop())

        values = formGroup.prop().map (item, offset)->
            number = offset + 1
            row = config.buildItemRow(newRowBuilder(number, item), number, item)
            return row
            
        return values


    formGroup.addItem = (e)->
        e.preventDefault()
        emptyItem = buildNewItem(config)
        formGroup.prop().push(emptyItem)
        return

    formGroup.unserialize = (itemsData)->
        newItems = []


        for rawItemData in itemsData
            if config.translateFieldToNumberedValues?
                itemData = {}
                itemData[config.translateFieldToNumberedValues] = rawItemData
            else
                itemData = rawItemData
            newItems.push(buildNewItem(config, itemData))

        # build a blank single item if there was not data
        if not itemsData or not itemsData.length
            newItems.push(buildNewItem(config))
                        
        formGroup.prop(newItems)

        return

    formGroup.serialize = ()->
        if config.translateFieldToNumberedValues?
            # translate [{address: "blah",}, {address: "blah2"}] to ["blah","blah2"]
            serializedData = []
            for prop in formGroup.prop()
                serializedData.push(prop[config.translateFieldToNumberedValues]())
        else
            serializedData = formGroup.prop()
        return serializedData


    return formGroup

module.exports = groupBuilder
