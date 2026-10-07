import { AlertCircle, Bot, ClipboardCopy, RotateCcw, Send, User2 } from "lucide-react"
import { useEffect, useState } from "react"
import { useTranslation } from "react-i18next"
import ReactMarkdown from "react-markdown"

import { useAuth } from "../contexts/AuthContext.tsx"
import { useChat } from "../hooks/useChat.ts"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import { ChatMessageRole } from "../types/ChatMessageRole.ts"
import { UserRole } from "../types/CoreSwaggerTypes.ts"

export default function ChatPage() {
    const { hasRole, username } = useAuth()
    const { t } = useTranslation()
    const { messages, isLoading, sendMessage, clearConversation } = useChat()
    const { showClearConversationToast, showCopyConversationToast } = usePredefinedUserInput()

    const [input, setInput] = useState("")
    const [height, setHeight] = useState(0)

    useEffect(() => {
        const onResize = () => setHeight(window.innerHeight - 300)
        onResize()
        window.addEventListener("resize", onResize)
        return () => window.removeEventListener("resize", onResize)
    }, [])

    const handleMessageSent = async () => {
        const text = input.trim()
        if (!text || isLoading) {
            return
        }

        setInput("")
        const restored = await sendMessage(text)
        if (restored !== undefined) {
            setInput(restored)
        }
    }

    const handleKeyDown = (e: React.KeyboardEvent<HTMLTextAreaElement>) => {
        if (e.key === "Enter" && !e.shiftKey) {
            e.preventDefault()
            handleMessageSent()
        }
    }

    const handleConversationCleared = () => {
        showClearConversationToast(async () => clearConversation())
    }

    const handleConversationCopied = () => {
        showCopyConversationToast(async () => {
            const text = messages
                .filter(m => m.role !== ChatMessageRole.Error)
                .map(m => `${m.role === ChatMessageRole.User ? username : t("chat.label.assistant")}:\n${m.content}`)
                .join("\n\n---\n\n")
            await navigator.clipboard.writeText(text)
        })
    }

    return hasRole(UserRole.GenerativecontentEdit) && (
        <div className="flex flex-col" style={{ height }}>
            <div className="flex items-center justify-between pb-3 border-b border-gray-200 flex-shrink-0 px-2 md:px-0">
                <h1 className="text-base font-semibold text-gray-900">{t("chat.label.title")}</h1>
                <div className="flex items-center gap-2">
                    <button
                        onClick={handleConversationCopied}
                        disabled={messages.length === 0}
                        className="btn-icon disabled:opacity-30 disabled:cursor-not-allowed text-gray-500">
                        <ClipboardCopy size={16} />
                    </button>
                    <button
                        onClick={handleConversationCleared}
                        disabled={messages.length === 0 && !isLoading}
                        className="btn-icon disabled:opacity-30 disabled:cursor-not-allowed text-gray-500">
                        <RotateCcw size={16} />
                    </button>
                </div>
            </div>
            <div className="flex-1 overflow-y-auto py-4 space-y-5 min-h-0">
                {messages.map(message => (
                    <div
                        key={message.id}
                        className={`flex gap-3 items-end ${message.role === ChatMessageRole.User ? "flex-row-reverse" : "flex-row"}`}>
                        <div className={`flex-shrink-0 w-7 h-7 rounded-full flex items-center justify-center ${message.role === ChatMessageRole.User ? "bg-blue-600 text-white" : message.role === ChatMessageRole.Error ? "bg-red-100 text-red-500" : "bg-gray-200 text-gray-600"}`}>
                            {message.role === ChatMessageRole.User ? <User2 size={13} /> : message.role === ChatMessageRole.Error ? <AlertCircle size={13} /> : <Bot size={13} />}
                        </div>
                        <div className={`max-w-[78%] rounded-2xl px-4 py-2.5 text-sm leading-relaxed ${message.role === ChatMessageRole.User ? "bg-blue-600 text-white rounded-br-sm" : message.role === ChatMessageRole.Error ? "bg-red-50 text-red-700 rounded-bl-sm" : "bg-gray-100 text-gray-900 rounded-bl-sm"}`}>
                            {message.role === ChatMessageRole.Assistant ? (
                                <div className="prose prose-sm max-w-none
                                    prose-p:my-1 prose-p:leading-relaxed
                                    prose-headings:mt-3 prose-headings:mb-1 prose-headings:font-semibold
                                    prose-ul:my-1 prose-ol:my-1 prose-li:my-0
                                    prose-pre:bg-gray-200 prose-pre:text-gray-800 prose-pre:text-xs prose-pre:rounded-lg
                                    prose-code:bg-gray-200 prose-code:px-1 prose-code:py-0.5 prose-code:rounded prose-code:text-xs prose-code:font-mono prose-code:before:content-none prose-code:after:content-none
                                    prose-a:text-blue-600 prose-a:no-underline hover:prose-a:underline
                                    prose-strong:font-semibold prose-blockquote:border-l-gray-300 prose-blockquote:text-gray-600">
                                    <ReactMarkdown>{message.content}</ReactMarkdown>
                                </div>
                            ) : (
                                <p className="whitespace-pre-wrap">{message.content}</p>
                            )}
                        </div>
                    </div>
                ))}
                {isLoading && (
                    <div className="flex gap-3 items-end">
                        <div className="flex-shrink-0 w-7 h-7 rounded-full flex items-center justify-center bg-gray-200 text-gray-600">
                            <Bot size={13} />
                        </div>
                        <div className="bg-gray-100 rounded-2xl rounded-bl-sm px-4 py-3 flex items-center gap-1">
                            <span className="w-1.5 h-1.5 rounded-full bg-gray-400 animate-bounce [animation-delay:-0.3s]" />
                            <span className="w-1.5 h-1.5 rounded-full bg-gray-400 animate-bounce [animation-delay:-0.15s]" />
                            <span className="w-1.5 h-1.5 rounded-full bg-gray-400 animate-bounce" />
                        </div>
                    </div>
                )}
            </div>
            <div className="flex-shrink-0 pt-4 border-t border-gray-200 px-2 md:px-0">
                <div className="flex items-center gap-2 bg-white border border-gray-300 rounded-xl px-3 py-2.5 focus-within:border-blue-400 focus-within:ring-1 focus-within:ring-blue-400 transition-all">
                    <textarea
                        rows={1}
                        value={input}
                        onChange={e => setInput(e.target.value)}
                        onKeyDown={handleKeyDown}
                        placeholder={t("chat.placeholder.input")}
                        className="flex-1 bg-transparent resize-none outline-none text-sm text-gray-900 placeholder-gray-400 max-h-32 leading-relaxed py-0"
                        style={{ fieldSizing: "content" } as React.CSSProperties} />
                    <button
                        onClick={handleMessageSent}
                        disabled={!input.trim() || isLoading}
                        className="flex-shrink-0 w-7 h-7 rounded-lg bg-blue-600 text-white flex items-center justify-center hover:bg-blue-700 disabled:opacity-30 disabled:cursor-not-allowed transition-colors">
                        <Send size={13} />
                    </button>
                </div>
            </div>
        </div>
    )
}
