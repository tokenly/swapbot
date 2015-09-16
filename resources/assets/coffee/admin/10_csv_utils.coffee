# csvutils functions

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

module.exports = csvutils

