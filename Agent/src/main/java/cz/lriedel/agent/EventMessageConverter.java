package cz.lriedel.agent;

import com.fasterxml.jackson.databind.ObjectMapper;
import cz.lriedel.agent.model.args.EventArgs;
import lombok.SneakyThrows;
import org.springframework.amqp.core.Message;
import org.springframework.amqp.support.converter.Jackson2JsonMessageConverter;
import org.springframework.amqp.support.converter.MessageConversionException;
import org.springframework.beans.factory.config.BeanDefinition;
import org.springframework.context.annotation.ClassPathScanningCandidateComponentProvider;
import org.springframework.core.type.filter.AssignableTypeFilter;
import org.springframework.stereotype.Component;

import java.util.Map;
import java.util.function.Function;
import java.util.stream.Collectors;

import static org.apache.commons.lang3.StringUtils.EMPTY;

@Component
class EventMessageConverter extends Jackson2JsonMessageConverter {

    private static final String EVENT_NAME_PROPERTY_KEY = "name";
    private static final String EVENT_ARGS_PROPERTY_KEY = "args";

    private final Map<String, Class<?>> supportedEvents;

    EventMessageConverter(ObjectMapper objectMapper) {
        super(objectMapper);
        this.supportedEvents = getSupportedEvents();
    }

    @SneakyThrows
    private static Map<String, Class<?>> getSupportedEvents() {
        ClassPathScanningCandidateComponentProvider scanner = new ClassPathScanningCandidateComponentProvider(false);
        scanner.addIncludeFilter(new AssignableTypeFilter(EventArgs.class));

        return scanner.findCandidateComponents(EventMessageConverter.class.getPackageName()).stream().map(EventMessageConverter::getCandidateName)
                .filter(aClass -> aClass.getSimpleName().endsWith(EventArgs.class.getSimpleName()))
                .collect(Collectors.toMap(aClass -> aClass.getSimpleName().replaceAll(EventArgs.class.getSimpleName(), EMPTY), Function.identity()));
    }

    @SneakyThrows
    private static Class<?> getCandidateName(BeanDefinition candidate) {
        return Class.forName(candidate.getBeanClassName());
    }

    @SneakyThrows
    @Override
    public Object fromMessage(Message message) {
        Map<String, Object> map = objectMapper.readValue(message.getBody(), Map.class);

        try {
            return objectMapper.readValue(objectMapper.writeValueAsBytes(map.get(EVENT_ARGS_PROPERTY_KEY)),
                    supportedEvents.getOrDefault(map.get(EVENT_NAME_PROPERTY_KEY), Map.class));
        }
        catch (Exception e) {
            throw new MessageConversionException("Unable to convert the message.", e);
        }
    }
}
