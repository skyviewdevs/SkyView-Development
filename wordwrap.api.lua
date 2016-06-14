function getWordWrappedTable(rawText, lineLength)
    local endTable = {""}
    local remainingLength = 0
    local sentenceTable = {}
    for text in rawText:gmatch("%S+") do-- Split sentences up by spaces.
        table.insert(sentenceTable, text)
    end
    local lineNumber = 1
    local currentLineText = ""
    local tempString = ""

    while true do
        currentLineText = endTable[lineNumber]
        remainingLength = lineLength - #currentLineText --See how much space is left
        if sentenceTable[1] ~= nil then
            if remainingLength >= #sentenceTable[1] then -- It can fit on the current row.
                if endTable[lineNumber] == "" then
                    endTable[lineNumber] = sentenceTable[1]
                else
                    endTable[lineNumber] = endTable[lineNumber] .. " " .. sentenceTable[1]
                end
                table.remove(sentenceTable, 1)
            else
                lineNumber = lineNumber + 1
                endTable[lineNumber] = sentenceTable[1]
                table.remove(sentenceTable, 1)
            end
        else -- Time to exit; the sentences have been split up.
            break
        end
    end
    
    return endTable
end

local sentence = "this is a sentence"
print(textutils.serialise(getWordWrappedTable(sentence, 4)))