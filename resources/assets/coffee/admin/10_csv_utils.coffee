# csvutils functions

# csvString = sbAdmin.csvutils.dataToCSVString(rows)
# window.location = sbAdmin.csvutils.CSVDownloadHref(csvString)


sbAdmin.csvutils = do ()->
    csvutils = {}

    # clone an object
    csvutils.dataToCSVString = (rows)->
        csv = ''
        for row in rows
            rowText = '"'+row.map((text, i)->
                console.log "text=",text
                return text.replace(/"/g, '""')
            ).join('","')+'"'
            csv += rowText + "\n"
        return csv

    csvutils.CSVDownloadHref = (csvString)->
        return "data:application/csv;charset=utf-8," + encodeURIComponent(csvString);

    return csvutils

